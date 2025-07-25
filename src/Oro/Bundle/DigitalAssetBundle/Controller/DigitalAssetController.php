<?php

namespace Oro\Bundle\DigitalAssetBundle\Controller;

use Oro\Bundle\AttachmentBundle\Helper\FieldConfigHelper;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\AttachmentBundle\Provider\FileConstraintsProvider;
use Oro\Bundle\DigitalAssetBundle\Entity\DigitalAsset;
use Oro\Bundle\DigitalAssetBundle\Form\Type\DigitalAssetInDialogType;
use Oro\Bundle\DigitalAssetBundle\Form\Type\DigitalAssetType;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller for DigitalAsset entity.
 */
class DigitalAssetController extends AbstractController
{
    #[Route(path: '/', name: 'oro_digital_asset_index')]
    #[Template('@OroDigitalAsset/DigitalAsset/index.html.twig')]
    #[AclAncestor('oro_digital_asset_view')]
    public function indexAction(): array
    {
        return [
            'entity_class' => DigitalAsset::class,
        ];
    }

    /**
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/create', name: 'oro_digital_asset_create')]
    #[Template('@OroDigitalAsset/DigitalAsset/update.html.twig')]
    #[AclAncestor('oro_digital_asset_create')]
    public function createAction()
    {
        return $this->update(new DigitalAsset());
    }

    /**
     * @param DigitalAsset $digitalAsset
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_digital_asset_update', requirements: ['id' => '\d+'])]
    #[Template('@OroDigitalAsset/DigitalAsset/update.html.twig')]
    #[AclAncestor('oro_digital_asset_update')]
    public function updateAction(DigitalAsset $digitalAsset)
    {
        return $this->update($digitalAsset);
    }

    /**
     * @param DigitalAsset $digitalAsset
     *
     * @return array|RedirectResponse
     */
    protected function update(DigitalAsset $digitalAsset)
    {
        return $this->container->get(UpdateHandlerFacade::class)
            ->update(
                $digitalAsset,
                $this->createForm(DigitalAssetType::class, $digitalAsset),
                $this->container->get(TranslatorInterface::class)->trans('oro.digitalasset.controller.saved.message')
            );
    }

    /**
     *
     * @param string $parentEntityClass
     * @param string $parentEntityFieldName
     * @return array|RedirectResponse
     */
    #[Route(
        path: '/widget/choose/{parentEntityClass}/{parentEntityFieldName}',
        name: 'oro_digital_asset_widget_choose'
    )]
    #[Template('@OroDigitalAsset/DigitalAsset/widget/choose.html.twig')]
    #[AclAncestor('oro_digital_asset_create')]
    public function chooseAction(string $parentEntityClass, string $parentEntityFieldName)
    {
        try {
            $resolvedParentEntityClass = $this->container->get(EntityClassNameHelper::class)
                ->resolveEntityClass($parentEntityClass);
        } catch (EntityAliasNotFoundException $e) {
            $this->container->get(LoggerInterface::class)
                ->warning(sprintf('Entity alias for %s was not found', $parentEntityClass), ['exception' => $e]);

            throw new NotFoundHttpException();
        }

        $attachmentEntityFieldConfig = $this->container->get(AttachmentEntityConfigProviderInterface::class)
            ->getFieldConfig($resolvedParentEntityClass, $parentEntityFieldName);

        if (!$attachmentEntityFieldConfig) {
            throw new NotFoundHttpException();
        }

        $isImageType = FieldConfigHelper::isImageField($attachmentEntityFieldConfig->getId());
        $mimeTypes = $this->container->get(FileConstraintsProvider::class)
            ->getAllowedMimeTypesForEntityField($resolvedParentEntityClass, $parentEntityFieldName);
        $maxFileSize = $this->container->get(FileConstraintsProvider::class)
            ->getMaxSizeForEntityField($resolvedParentEntityClass, $parentEntityFieldName);

        return $this->handleChooseForm($isImageType, $mimeTypes, $maxFileSize);
    }

    /**
     * @param bool $isImageType
     * @param array $mimeTypes
     * @param int $maxFileSize
     *
     * @return array|RedirectResponse
     */
    protected function handleChooseForm(bool $isImageType, array $mimeTypes, int $maxFileSize)
    {
        $form = $this->createForm(
            DigitalAssetInDialogType::class,
            new DigitalAsset(),
            [
                'is_image_type' => $isImageType,
                'mime_types' => $mimeTypes,
                'max_file_size' => $maxFileSize,
            ]
        );

        return $this->container->get(UpdateHandlerFacade::class)
            ->update(
                $form->getData(),
                $form,
                '',
                null,
                null,
                function ($entity, FormInterface $form, Request $request) use ($mimeTypes, $maxFileSize, $isImageType) {
                    return [
                        'saved' => $form->isSubmitted() && $form->isValid(),
                        'digital_asset' => $entity,
                        'is_image_type' => $isImageType,
                        'grid_name' => $isImageType
                            ? 'digital-asset-select-image-grid'
                            : 'digital-asset-select-file-grid',
                        'grid_params' => ['mime_types' => $mimeTypes, 'max_file_size' => $maxFileSize],
                        'form' => $form->createView(),
                    ];
                }
            );
    }

    /**
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/widget/choose-image', name: 'oro_digital_asset_widget_choose_image')]
    #[Template('@OroDigitalAsset/DigitalAsset/widget/choose.html.twig')]
    #[AclAncestor('oro_digital_asset_create')]
    public function chooseImageAction()
    {
        $mimeTypes = $this->container->get(FileConstraintsProvider::class)->getImageMimeTypes();
        $maxFileSize = $this->container->get(FileConstraintsProvider::class)->getMaxSize();

        return $this->handleChooseForm(true, $mimeTypes, $maxFileSize);
    }

    /**
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/widget/choose-file', name: 'oro_digital_asset_widget_choose_file')]
    #[Template('@OroDigitalAsset/DigitalAsset/widget/choose.html.twig')]
    #[AclAncestor('oro_digital_asset_create')]
    public function chooseFileAction()
    {
        $mimeTypes = $this->container->get(FileConstraintsProvider::class)->getFileMimeTypes();
        $maxFileSize = $this->container->get(FileConstraintsProvider::class)->getMaxSize();

        return $this->handleChooseForm(false, $mimeTypes, $maxFileSize);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                UpdateHandlerFacade::class,
                TranslatorInterface::class,
                LoggerInterface::class,
                EntityClassNameHelper::class,
                AttachmentEntityConfigProviderInterface::class,
                FileConstraintsProvider::class,
            ]
        );
    }
}
