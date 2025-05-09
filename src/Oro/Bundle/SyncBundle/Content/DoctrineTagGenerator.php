<?php

namespace Oro\Bundle\SyncBundle\Content;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Symfony\Component\Form\FormInterface;

/**
 * Generates tags for content.
 */
class DoctrineTagGenerator implements TagGeneratorInterface
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    public function __construct(ManagerRegistry $doctrine, EntityClassResolver $entityClassResolver)
    {
        $this->doctrine = $doctrine;
        $this->entityClassResolver = $entityClassResolver;
    }

    #[\Override]
    public function supports($data)
    {
        if (null === $data) {
            return false;
        }

        if ($data instanceof FormInterface) {
            $data = $data->getData();

            return null !== $data && $this->supports($data);
        }

        if (is_object($data)) {
            return null !== $this->doctrine->getManagerForClass(ClassUtils::getClass($data));
        } elseif (is_string($data)) {
            return null !== $this->doctrine->getManagerForClass(ClassUtils::getRealClass($data));
        }

        return false;
    }

    #[\Override]
    public function generate($data, $includeCollectionTag = false, $processNestedData = false)
    {
        if ($data instanceof FormInterface) {
            return $this->generate($data->getData(), $includeCollectionTag, $processNestedData);
        }

        return $this->getTags($data, $includeCollectionTag, $processNestedData);
    }

    /**
     * @param mixed $data
     * @param bool  $includeCollectionTag
     * @param bool  $processNestedData
     *
     * @return array
     */
    protected function getTags($data, $includeCollectionTag, $processNestedData)
    {
        $tags = [];

        if (is_object($data)) {
            $class = ClassUtils::getClass($data);
            /** @var EntityManagerInterface $em */
            $em = $this->doctrine->getManagerForClass($class);
            $uow = $em->getUnitOfWork();
            // tag only in case if it's not a new object
            if ($this->isNewEntity($data, $uow)) {
                $tags[] = implode(
                    '_',
                    array_merge([$this->convertToTag($class)], $uow->getEntityIdentifier($data))
                );
                if ($processNestedData) {
                    $tags = array_merge(
                        $tags,
                        $this->collectNestedDataTags($data, $em->getClassMetadata($class))
                    );
                }
            }
        } else {
            $class = $this->entityClassResolver->getEntityClass(ClassUtils::getRealClass($data));
        }

        if ($includeCollectionTag) {
            $tags[] = $this->convertToTag($class) . self::COLLECTION_SUFFIX;
        }

        return $tags;
    }

    /**
     * @param mixed         $data
     * @param ClassMetadata $metadata
     *
     * @return array
     */
    protected function collectNestedDataTags($data, ClassMetadata $metadata)
    {
        $tags = [];

        foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
            $value = $metadata->reflFields[$fieldName]->getValue($data);
            if (null !== $value) {
                // skip DoctrineTagGenerator#supports() call due to doctrine association mapping always contain entities
                if ($mapping['type'] & ClassMetadata::TO_ONE) {
                    $unwrappedValue = [$value];
                } elseif ($value instanceof PersistentCollection) {
                    $unwrappedValue = $value->unwrap();
                } else {
                    $unwrappedValue = $value;
                }
                foreach ($unwrappedValue as $entity) {
                    // allowed one nested level
                    $tags = array_merge($tags, $this->generate($entity));
                }
            }
        }

        return $tags;
    }

    /**
     * Convert entity FQCN to tag
     *
     * @param string $data
     *
     * @return string
     */
    protected function convertToTag($data)
    {
        return preg_replace('#[^a-z]+#i', '_', $data);
    }

    /**
     * @param object     $entity
     * @param UnitOfWork $uow
     *
     * @return bool
     */
    protected function isNewEntity($entity, UnitOfWork $uow)
    {
        $entityState = $uow->getEntityState($entity);

        return
            $entityState !== UnitOfWork::STATE_NEW
            && $entityState !== UnitOfWork::STATE_DETACHED
            && !$uow->isScheduledForInsert($entity);
    }
}
