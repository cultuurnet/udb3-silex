<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Doctrine;

use CultuurNet\UDB3\Label\ReadModels\Doctrine\AbstractDBALRepository;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;
use ValueObjects\Number\Integer as IntegerValue;
use ValueObjects\StringLiteral\StringLiteral;

class DBALWriteRepository extends AbstractDBALRepository implements WriteRepositoryInterface
{
    public function save(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null
    ) {
        $queryBuilder = $this->createQueryBuilder()
            ->insert($this->getTableName()->toNative())
            ->values([
                SchemaConfigurator::UUID_COLUMN => '?',
                SchemaConfigurator::NAME_COLUMN => '?',
                SchemaConfigurator::VISIBLE_COLUMN => '?',
                SchemaConfigurator::PRIVATE_COLUMN => '?',
                SchemaConfigurator::PARENT_UUID_COLUMN => '?',
            ])
            ->setParameters([
                $uuid->toNative(),
                $name->toNative(),
                $visibility === Visibility::VISIBLE() ? 1 : 0,
                $privacy === Privacy::PRIVACY_PRIVATE() ? 1 : 0,
                $parentUuid ? $parentUuid->toNative() : null,
            ]);

        $queryBuilder->execute();
    }


    public function updateVisible(UUID $uuid)
    {
        $this->executeUpdate(
            SchemaConfigurator::VISIBLE_COLUMN,
            true,
            $uuid
        );
    }


    public function updateInvisible(UUID $uuid)
    {
        $this->executeUpdate(
            SchemaConfigurator::VISIBLE_COLUMN,
            false,
            $uuid
        );
    }


    public function updatePublic(UUID $uuid)
    {
        $this->executeUpdate(
            SchemaConfigurator::PRIVATE_COLUMN,
            false,
            $uuid
        );
    }


    public function updatePrivate(UUID $uuid)
    {
        $this->executeUpdate(
            SchemaConfigurator::PRIVATE_COLUMN,
            true,
            $uuid
        );
    }


    public function updateCountIncrement(UUID $uuid)
    {
        $this->executeCountUpdate(
            new IntegerValue(+1),
            $uuid
        );
    }


    public function updateCountDecrement(UUID $uuid)
    {
        $this->executeCountUpdate(
            new IntegerValue(-1),
            $uuid
        );
    }

    /**
     * @param string $column
     * @param bool $value
     */
    private function executeUpdate(
        $column,
        $value,
        UUID $uuid
    ) {
        $queryBuilder = $this->createQueryBuilder()
            ->update($this->getTableName()->toNative())
            ->set($column, '?')
            ->where(SchemaConfigurator::UUID_COLUMN . ' = ?')
            ->setParameters([
                $value ? 1 : 0,
                $uuid->toNative(),
            ]);

        $queryBuilder->execute();
    }


    private function executeCountUpdate(
        IntegerValue $value,
        UUID $uuid
    ) {
        $currentCount = $this->getCurrentCount($uuid)->toNative();
        $newCount = $currentCount + $value->toNative();

        $queryBuilder = $this->createQueryBuilder()
            ->update($this->getTableName()->toNative())
            ->set(
                SchemaConfigurator::COUNT_COLUMN,
                $newCount < 0 ? 0 : $newCount
            )
            ->where(SchemaConfigurator::UUID_COLUMN . ' = ?')
            ->setParameters([$uuid->toNative()]);

        $queryBuilder->execute();
    }

    private function getCurrentCount(UUID $uuid): IntegerValue
    {
        $queryBuilder = $this->createQueryBuilder()
            ->select([SchemaConfigurator::COUNT_COLUMN])
            ->from($this->getTableName()->toNative())
            ->where(SchemaConfigurator::UUID_COLUMN . ' = ?')
            ->setParameters([$uuid->toNative()]);

        $statement = $queryBuilder->execute();
        $row = $statement->fetch(\PDO::FETCH_NUM);

        return new IntegerValue($row[0]);
    }
}
