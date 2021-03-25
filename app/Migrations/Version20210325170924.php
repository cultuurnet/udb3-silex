<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version20210325170924 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('offer_metadata');
        $table->addColumn(
            'id',
            'guid',
            [
                'length' => 36,
                'notnull' => true,
            ]
        );
        $table->addColumn(
            'createdByApiConsumer',
            'string',
            [
                'length' => 255,
                'notnull' => true,
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('offer_metadata');
    }
}
