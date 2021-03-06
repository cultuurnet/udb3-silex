<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\Silex\Labels\LabelServiceProvider;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160817112023 extends AbstractMigration
{
    public const LABEL_ID_COLUMN = 'label_id';
    public const ROLE_ID_COLUMN = 'role_id';


    public function up(Schema $schema)
    {
        $userRoleTable = $schema->createTable(
            LabelServiceProvider::LABEL_ROLES_TABLE
        );

        $userRoleTable->addColumn(self::LABEL_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->addColumn(self::ROLE_ID_COLUMN, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $userRoleTable->setPrimaryKey(
            [
                self::LABEL_ID_COLUMN,
                self::ROLE_ID_COLUMN,
            ]
        );
    }


    public function down(Schema $schema)
    {
        $schema->dropTable(LabelServiceProvider::LABEL_ROLES_TABLE);
    }
}
