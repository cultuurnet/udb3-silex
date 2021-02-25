<?php

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160718210058 extends AbstractMigration
{
    public const ROLES_SEARCH = 'roles_search';

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $table = $schema->createTable(self::ROLES_SEARCH);

        $table->addColumn('uuid', 'guid', ['length' => 36]);
        $table->addColumn('name', 'string')->setLength(255);

        $table->setPrimaryKey(['uuid']);
        $table->addUniqueIndex(['uuid', 'name']);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $schema->dropTable(self::ROLES_SEARCH);
    }
}
