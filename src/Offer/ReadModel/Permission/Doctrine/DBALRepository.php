<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine;

use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionQueryInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionRepositoryInterface;
use ValueObjects\StringLiteral\StringLiteral;

class DBALRepository implements PermissionRepositoryInterface, PermissionQueryInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var StringLiteral
     */
    protected $idField;

    /**
     * @var StringLiteral
     */
    protected $tableName;

    /**
     * @param StringLiteral $tableName
     *  The name of the table where the permissions are stored.
     *
     * @param Connection $connection
     *  A database connection.
     *
     * @param StringLiteral $idField
     *  The name of the column that holds the offer identifier.
     *
     */
    public function __construct(
        StringLiteral $tableName,
        Connection $connection,
        StringLiteral $idField
    ) {
        $this->tableName = $tableName;
        $this->connection = $connection;
        $this->idField = $idField;
    }

    /**
     * @inheritdoc
     */
    public function getEditableOffers(StringLiteral $uitId)
    {
        $q = $this->connection->createQueryBuilder();
        $q->select($this->idField->toNative())
            ->from($this->tableName->toNative())
            ->where('user_id = :userId')
            ->setParameter(':userId', $uitId->toNative());

        $results = $q->execute();

        $events = [];
        while ($id = $results->fetchColumn(0)) {
            $events[] = new StringLiteral($id);
        }

        return $events;
    }

    /**
     * @inheritdoc
     */
    public function markOfferEditableByUser(StringLiteral $eventId, StringLiteral $uitId)
    {
        try {
            $this->connection->insert(
                $this->tableName->toNative(),
                [
                    $this->idField->toNative() => $eventId->toNative(),
                    'user_id' => $uitId->toNative(),
                ]
            );
        } catch (UniqueConstraintViolationException $e) {
            // Intentionally catching database exception occurring when the
            // permission record is already in place.
        }
    }

    public function markOfferEditableByNewUser(StringLiteral $eventId, StringLiteral $userId): void
    {
        $this->connection->update(
            $this->tableName->toNative(),
            ['user_id' => $userId->toNative()],
            [$this->idField->toNative() => $eventId->toNative()]
        );
    }
}
