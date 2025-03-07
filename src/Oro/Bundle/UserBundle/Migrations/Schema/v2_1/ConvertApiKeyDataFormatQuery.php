<?php

namespace Oro\Bundle\UserBundle\Migrations\Schema\v2_1;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Psr\Log\LoggerInterface;

/**
 * Converts data of api_key column of oro_user_api table to coded format.
 */
class ConvertApiKeyDataFormatQuery extends ParametrizedSqlMigrationQuery
{
    /** @var SymmetricCrypterInterface */
    protected $crypter;

    public function __construct(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
        parent::__construct();
    }

    #[\Override]
    protected function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $keys = $this->connection->createQueryBuilder()
            ->select('k.id, k.api_key')
            ->from('oro_user_api', 'k')
            ->execute()
            ->fetchAllAssociative();
        foreach ($keys as $key) {
            $this->addSql(
                'UPDATE oro_user_api SET api_key = :api_key WHERE id = :id',
                [
                    'id'      => $key['id'],
                    'api_key' => $this->crypter->encryptData($key['api_key'])
                ],
                [
                    'id'      => Types::INTEGER,
                    'api_key' => Types::STRING
                ]
            );
        }

        parent::processQueries($logger, $dryRun);
    }
}
