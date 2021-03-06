<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use CultuurNet\UDB3\SavedSearches\Doctrine\SchemaConfigurator;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181023121440 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $table = $schema->createTable('saved_searches_sapi2');

        $table->addColumn(SchemaConfigurator::ID, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::USER, Type::GUID)
            ->setLength(36)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::NAME, Type::STRING)
            ->setLength(255)
            ->setNotnull(true);

        $table->addColumn(SchemaConfigurator::QUERY, Type::TEXT)
            ->setNotnull(true);

        $table->addIndex([SchemaConfigurator::ID]);
        $table->addIndex([SchemaConfigurator::USER]);
    }


    public function down(Schema $schema)
    {
        $schema->dropTable('saved_searches_sapi2');
    }
}
