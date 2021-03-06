<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStore;
use Broadway\EventStore\EventStreamNotFoundException;
use Broadway\Serializer\Serializer;
use CultuurNet\UDB3\Silex\AggregateType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;

/**
 * Event store making use of Doctrine DBAL and aware of the aggregate type.
 *
 * Based on Broadways DBALEventStore.
 */
class AggregateAwareDBALEventStore implements EventStore
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Serializer
     */
    private $payloadSerializer;

    /**
     * @var Serializer
     */
    private $metadataSerializer;

    /**
     * @var Statement|null
     */
    private $loadStatement = null;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private $aggregateType;

    public function __construct(
        Connection $connection,
        Serializer $payloadSerializer,
        Serializer $metadataSerializer,
        string $tableName,
        AggregateType $aggregateType
    ) {
        $this->connection         = $connection;
        $this->payloadSerializer  = $payloadSerializer;
        $this->metadataSerializer = $metadataSerializer;
        $this->tableName          = $tableName;
        $this->aggregateType      = (string) $aggregateType;
    }

    /**
     * {@inheritDoc}
     */
    public function load($id)
    {
        return $this->loadDomainEventStream($id, 0);
    }

    /**
     * {@inheritDoc}
     */
    public function loadFromPlayhead($id, $playhead)
    {
        return $this->loadDomainEventStream($id, $playhead);
    }

    private function loadDomainEventStream($id, $playhead)
    {
        $statement = $this->prepareLoadStatement();
        $statement->bindValue('uuid', $id);
        $statement->bindValue('playhead', $playhead);
        $statement->execute();

        $events = [];
        while ($row = $statement->fetch()) {
            // Drop events that do not match the aggregate type.
            if ($row['aggregate_type'] !== $this->aggregateType) {
                continue;
            }
            $events[] = $this->deserializeEvent($row);
        }

        if (empty($events)) {
            throw new EventStreamNotFoundException(sprintf('EventStream not found for aggregate with id %s', $id));
        }

        return new DomainEventStream($events);
    }

    /**
     * {@inheritDoc}
     */
    public function append($id, DomainEventStream $eventStream)
    {
        // The original Broadway DBALEventStore implementation did only check
        // the type of $id. It is better to test all UUIDs inside the event
        // stream.
        $this->guardStream($eventStream);

        // Make the transaction more robust by using the transactional statement.
        $this->connection->transactional(function (Connection $connection) use ($eventStream) {
            try {
                foreach ($eventStream as $domainMessage) {
                    $this->insertMessage($connection, $domainMessage);
                }
            } catch (DBALException $exception) {
                throw DBALEventStoreException::create($exception);
            }
        });
    }


    private function insertMessage(Connection $connection, DomainMessage $domainMessage)
    {
        $data = [
            'uuid'           => (string) $domainMessage->getId(),
            'playhead'       => $domainMessage->getPlayhead(),
            'metadata'       => json_encode($this->metadataSerializer->serialize($domainMessage->getMetadata())),
            'payload'        => json_encode($this->payloadSerializer->serialize($domainMessage->getPayload())),
            'recorded_on'    => $domainMessage->getRecordedOn()->toString(),
            'type'           => $domainMessage->getType(),
            'aggregate_type' => $this->aggregateType,
        ];

        $connection->insert($this->tableName, $data);
    }

    /**
     * @return Table|null
     */
    public function configureSchema(Schema $schema)
    {
        if ($schema->hasTable($this->tableName)) {
            return null;
        }

        return $this->configureTable();
    }


    public function configureTable()
    {
        $schema = new Schema();

        $table = $schema->createTable($this->tableName);

        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('uuid', 'guid', ['length' => 36]);
        $table->addColumn('playhead', 'integer', ['unsigned' => true]);
        $table->addColumn('payload', 'text');
        $table->addColumn('metadata', 'text');
        $table->addColumn('recorded_on', 'string', ['length' => 32]);
        $table->addColumn('type', 'string', ['length' => 128]);
        $table->addColumn('aggregate_type', 'string', ['length' => 128]);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['uuid', 'playhead']);

        $table->addIndex(['type']);
        $table->addIndex(['aggregate_type']);

        return $table;
    }

    private function prepareLoadStatement(): Statement
    {
        if (null === $this->loadStatement) {
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder->select(
                ['uuid', 'playhead', 'metadata', 'payload', 'recorded_on', 'aggregate_type']
            )
                ->from($this->tableName)
                ->where('uuid = :uuid')
                ->andWhere('playhead >= :playhead')
                ->orderBy('playhead', 'ASC');

            $this->loadStatement = $this->connection->prepare(
                $queryBuilder->getSQL()
            );
        }

        return $this->loadStatement;
    }

    private function deserializeEvent(array $row): DomainMessage
    {
        return new DomainMessage(
            $row['uuid'],
            $row['playhead'],
            $this->metadataSerializer->deserialize(json_decode($row['metadata'], true)),
            $this->payloadSerializer->deserialize(json_decode($row['payload'], true)),
            DateTime::fromString($row['recorded_on'])
        );
    }

    /**
     * Ensure that an error will be thrown if the ID in the domain messages is
     * not something that can be converted to a string.
     *
     * If we let this move on without doing this DBAL will eventually
     * give us a hard time but the true reason for the problem will be
     * obfuscated.
     */
    private function guardStream(DomainEventStream $eventStream): void
    {
        foreach ($eventStream as $domainMessage) {
            /** @var DomainMessage $domainMessage */
            $id = (string) $domainMessage->getId();
        }
    }
}
