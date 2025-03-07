<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Make column not nullable after it is filled for all rows.
 */
class MakeUsernameLowercaseFieldNotNull implements Migration, OrderedMigrationInterface
{
    #[\Override]
    public function getOrder()
    {
        return 20;
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_user');
        $table->changeColumn('username_lowercase', ['notnull' => true]);
    }
}
