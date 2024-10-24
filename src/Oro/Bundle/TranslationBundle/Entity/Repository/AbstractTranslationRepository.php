<?php

namespace Oro\Bundle\TranslationBundle\Entity\Repository;

use Gedmo\Translatable\Entity\Repository\TranslationRepository;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Abstract Gedmo translation repository for translation dictionaries.
 * It can speed up translation updating process.
 */
abstract class AbstractTranslationRepository extends TranslationRepository implements TranslationRepositoryInterface
{
    protected function doUpdateTranslations(string $className, string $fieldName, array $data, string $locale): void
    {
        if (!$data) {
            return;
        }

        $connection = $this->getEntityManager()->getConnection();
        $connection->beginTransaction();

        try {
            $qb = $this->createQueryBuilder('entity');
            $qb->select('entity.id', 'entity.foreignKey', 'entity.content')
                ->where(
                    $qb->expr()->in('entity.foreignKey', ':foreignKey'),
                    $qb->expr()->eq('entity.locale', ':locale'),
                    $qb->expr()->eq('entity.objectClass', ':objectClass'),
                    $qb->expr()->eq('entity.field', ':field')
                )
                ->setParameter('foreignKey', array_keys($data))
                ->setParameter('locale', $locale)
                ->setParameter('objectClass', $className)
                ->setParameter('field', $fieldName);

            $result = $qb->getQuery()->getArrayResult();
            $tableName = $this->getClassMetadata()->getTableName();

            foreach ($result as $trans) {
                $value = $data[$trans['foreignKey']];
                unset($data[$trans['foreignKey']]);

                if ($trans['content'] === $value) {
                    continue;
                }

                $connection->update(
                    $tableName,
                    ['content' => $value],
                    ['id' => $trans['id']]
                );
            }

            if ($data) {
                $params = [];
                foreach ($data as $combinedCode => $value) {
                    $params[] = $combinedCode;
                    $params[] = $locale;
                    $params[] = $className;
                    $params[] = $fieldName;
                    $params[] = $value;
                }

                $valuesPlaceholder = implode(', ', array_fill(0, count($data), '(?, ?, ?, ?, ?)'));
                $sql = sprintf(
                    'INSERT INTO %s (foreign_key, locale, object_class, field, content) VALUES %s',
                    $tableName,
                    $valuesPlaceholder
                );

                $connection->executeQuery($sql, $params);
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }

    protected function doUpdateDefaultTranslations(
        string $className,
        string $valueFieldName,
        string $criteriaFieldName,
        string $criteriaColumnName,
        array $data
    ): void {
        if (!$data) {
            return;
        }

        $alias = 'entity';
        $connection = $this->_em->getConnection();
        $connection->beginTransaction();

        try {
            $qb = $this->_em->createQueryBuilder();
            $criteriaField = QueryBuilderUtil::getField($alias, $criteriaFieldName);
            $qb->select(QueryBuilderUtil::getField($alias, $valueFieldName), $criteriaField)
                ->from($className, $alias)
                ->where($qb->expr()->in($criteriaField, ':param'))
                ->setParameter('param', array_keys($data));

            $result = $qb->getQuery()->getArrayResult();

            foreach ($result as $type) {
                $value = $data[$type[$criteriaFieldName]];

                if ($type[$criteriaFieldName] !== $value) {
                    $connection->update(
                        $this->getEntityManager()->getClassMetadata($className)->getTableName(),
                        [$valueFieldName => $value],
                        [$criteriaColumnName => $type[$criteriaFieldName]]
                    );
                }
            }

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();

            throw $e;
        }
    }
}
