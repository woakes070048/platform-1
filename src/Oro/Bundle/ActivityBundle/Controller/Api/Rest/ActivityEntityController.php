<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityEntityApiEntityManager;
use Oro\Bundle\ActivityBundle\Exception\InvalidArgumentException;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API CRUD controller for activity entities.
 */
class ActivityEntityController extends RestController
{
    /**
     * Get entities associated with the specified activity.
     *
     * @param Request $request
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @ApiDoc(
     *      description="Get entities associated with the specified activity",
     *      resource=true
     * )
     *
     * @return Response
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. Defaults to 10.',
        nullable: true
    )]
    public function cgetAction(Request $request, $activity, int $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(['id' => ['=', $id]]);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Adds an association between an activity and a target entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     *
     * @ApiDoc(
     *      description="Adds an association between an activity and a target entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function postAction($activity, int $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($activity, true));

        return $this->handleUpdateRequest($id);
    }

    /**
     * Deletes an association between an activity and a target entity.
     *
     * @param string $activity The type of the activity entity.
     * @param int    $id       The id of the activity entity.
     * @param string $entity   The type of the target entity.
     * @param mixed  $entityId The id of the target entity.
     *
     * @ApiDoc(
     *      description="Deletes an association between an activity and a target entity",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function deleteAction($activity, int $id, $entity, $entityId)
    {
        $manager       = $this->getManager();
        $activityClass = $manager->resolveEntityClass($activity, true);
        $manager->setClass($activityClass);

        $id = new RelationIdentifier(
            $activityClass,
            $id,
            $manager->resolveEntityClass($entity, true),
            $entityId
        );

        try {
            return $this->handleDeleteRequest($id);
        } catch (InvalidArgumentException $exception) {
            return $this->handleDeleteError($exception->getMessage(), Response::HTTP_BAD_REQUEST, $id);
        } catch (\Exception $e) {
            return $this->handleDeleteError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $id);
        }
    }

    /**
     * @param string             $message
     * @param int                $code
     * @param RelationIdentifier $id
     *
     * @return Response
     */
    protected function handleDeleteError($message, $code, RelationIdentifier $id)
    {
        $view = $this->view(['message' => $message], $code);
        return $this->buildResponse(
            $view,
            self::ACTION_DELETE,
            [
                'ownerEntityClass'  => $id->getOwnerEntityClass(),
                'ownerEntityId'     => $id->getOwnerEntityId(),
                'targetEntityClass' => $id->getTargetEntityClass(),
                'targetEntityId'    => $id->getTargetEntityId(),
                'success'           => false
            ]
        );
    }

    /**
     * Get entity manager
     *
     * @return ActivityEntityApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_entity.api');
    }

    #[\Override]
    public function getFormHandler()
    {
        return $this->container->get('oro_activity.form.handler.activity_entity.api');
    }

    #[\Override]
    protected function getDeleteHandler()
    {
        return $this->container->get('oro_activity.activity_entity_delete_handler.proxy');
    }
}
