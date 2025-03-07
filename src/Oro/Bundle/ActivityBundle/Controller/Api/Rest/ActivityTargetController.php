<?php

namespace Oro\Bundle\ActivityBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityBundle\Entity\Manager\ActivityTargetApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to get activity associations.
 */
class ActivityTargetController extends RestGetController
{
    /**
     * Get types of entities which can be associated with at least one activity type.
     *
     * @ApiDoc(
     *      description="Get types of entities which can be associated with at least one activity type",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getAllTypesAction()
    {
        $result = $this->getManager()->getTargetTypes();

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get types of activities which can be added to the specified entity type.
     *
     * @param string $entity The type of the target entity.
     *
     * @ApiDoc(
     *      description="Get types of activities which can be added to the specified entity type",
     *      resource=true
     * )
     *
     * @return Response
     */
    public function getActivityTypesAction($entity)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity, true));

        $result = $this->getManager()->getActivityTypes();

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * Get activities for the specified entity.
     *
     * @param Request $request
     * @param string $entity The type of the target entity.
     * @param mixed  $id     The id of the target entity.
     *
     * @ApiDoc(
     *      description="Get activities for the specified entity",
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
    public function getActivitiesAction(Request $request, $entity, $id)
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass($entity, true));

        $page  = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', self::ITEMS_PER_PAGE);

        $criteria = $this->buildFilterCriteria(['id' => ['=', $id]], [], ['id' => 'target.id']);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return ActivityTargetApiEntityManager
     */
    #[\Override]
    public function getManager()
    {
        return $this->container->get('oro_activity.manager.activity_target.api');
    }
}
