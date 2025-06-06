<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v1_15;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldSchema implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder(): int
    {
        return 2;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $schema->dropTable('oro_user_email_origin');
        $schema->dropTable('oro_email_to_folder');

        $emailTable = $schema->getTable('oro_email');
        $emailTable->dropColumn('is_seen');
        $emailTable->dropColumn('received');
    }
}
