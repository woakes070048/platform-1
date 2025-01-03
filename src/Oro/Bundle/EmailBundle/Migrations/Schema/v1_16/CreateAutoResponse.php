<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_16;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateAutoResponse implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 2;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroEmailAutoResponseRuleTable($schema);
        $this->createOroEmailAutoResponseRuleConditionTable($schema);

        $schema->getTable('oro_email_template')
            ->addColumn('visible', 'boolean', ['default' => '1']);
        $schema->getTable('oro_email')
            ->addColumn('acceptLanguageHeader', 'text', ['notnull' => false]);
    }

    private function createOroEmailAutoResponseRuleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_auto_response_rule');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('template_id', 'integer', ['notnull' => false]);
        $table->addColumn('mailbox_id', 'integer', ['notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addColumn('active', 'boolean');
        $table->addColumn('createdAt', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['template_id'], 'IDX_58CB592A5DA0FB8');
        $table->addIndex(['mailbox_id'], 'IDX_58CB592A66EC35CC');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_template'),
            ['template_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_mailbox'),
            ['mailbox_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function createOroEmailAutoResponseRuleConditionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_email_response_rule_cond');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('rule_id', 'integer', ['notnull' => false]);
        $table->addColumn('field', 'string', ['length' => 255]);
        $table->addColumn('filterType', 'string', ['length' => 255]);
        $table->addColumn('filterValue', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('position', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addIndex(['rule_id'], 'IDX_4132B1DB744E0351');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_email_auto_response_rule'),
            ['rule_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
