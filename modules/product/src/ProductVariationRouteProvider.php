<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Controller\ProductVariationController;
use Drupal\commerce_product\Controller\ProductVariationTranslationController;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the product variation entity.
 */
class ProductVariationRouteProvider extends AdminHtmlRouteProvider {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new ProductVariationRouteProvider.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($entity_type_manager, $entity_field_manager);

    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Core can't generate the translation routes until #3004038 gets fixed.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $default_parameters = [
        'commerce_product' => [
          'type' => 'entity:commerce_product',
        ],
        'commerce_product_variation' => [
          'type' => 'entity:' . 'commerce_product_variation',
        ],
      ];

      $overview_route = new Route($entity_type->getLinkTemplate('drupal:content-translation-overview'));
      $overview_route
        ->addDefaults([
          '_controller' => ProductVariationTranslationController::class . '::overview',
          'entity_type_id' => 'commerce_product_variation',
        ])
        ->setRequirements([
          '_entity_access' => 'commerce_product_variation.view',
          '_access_content_translation_overview' => 'commerce_product_variation',
        ])
        ->setOption('parameters', $default_parameters)
        ->setOption('_admin_route', TRUE);

      $add_route = new Route($entity_type->getLinkTemplate('drupal:content-translation-add'));
      $add_route
        ->addDefaults([
          '_controller' => ProductVariationTranslationController::class . '::add',
          'source' => NULL,
          'target' => NULL,
          '_title' => 'Add',
          'entity_type_id' => 'commerce_product_variation',
        ])
        ->setRequirements([
          '_entity_access' => 'commerce_product_variation.view',
          '_access_content_translation_manage' => 'create',
        ])
        ->setOption('parameters', $default_parameters + [
          'source' => [
            'type' => 'language',
          ],
          'target' => [
            'type' => 'language',
          ],
        ])
        ->setOption('_admin_route', TRUE);

      $edit_route = new Route($entity_type->getLinkTemplate('drupal:content-translation-edit'));
      $edit_route
        ->addDefaults([
          '_controller' => ProductVariationTranslationController::class . '::edit',
          'language' => NULL,
          '_title' => 'Edit',
          'entity_type_id' => 'commerce_product_variation',
        ])
        ->setRequirement('_access_content_translation_manage', 'update')
        ->setOption('parameters', $default_parameters + [
          'language' => [
            'type' => 'language',
          ],
        ])
        ->setOption('_admin_route', TRUE);

      $delete_route = new Route($entity_type->getLinkTemplate('drupal:content-translation-delete'));
      $delete_route
        ->addDefaults([
          '_entity_form' => 'commerce_product_variation.content_translation_deletion',
          'language' => NULL,
          '_title' => 'Delete',
          'entity_type_id' => 'commerce_product_variation',
        ])
        ->setRequirement('_access_content_translation_manage', 'delete')
        ->setOption('parameters', $default_parameters + [
          'language' => [
            'type' => 'language',
          ],
        ])
        ->setOption('_admin_route', TRUE);

      $collection->add('entity.commerce_product_variation.content_translation_overview', $overview_route);
      $collection->add('entity.commerce_product_variation.content_translation_add', $add_route);
      $collection->add("entity.commerce_product_variation.content_translation_edit", $edit_route);
      $collection->add("entity.commerce_product_variation.content_translation_delete", $delete_route);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    // The add-form route has no bundle argument because the bundle is selected
    // via the product ($product_type->getVariationTypeId()).
    $route = new Route($entity_type->getLinkTemplate('add-form'));
    $route
      ->setDefaults([
        '_entity_form' => 'commerce_product_variation.add',
        'entity_type_id' => 'commerce_product_variation',
        '_title_callback' => ProductVariationController::class . '::addTitle',
      ])
      ->setRequirement('_product_variation_create_access', 'TRUE')
      ->setOption('parameters', [
        'commerce_product' => [
          'type' => 'entity:commerce_product',
        ],
      ])
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getEditFormRoute($entity_type);
    $route->setDefault('_title_callback', ProductVariationController::class . '::editTitle');
    $route->setOption('parameters', [
      'commerce_product' => [
        'type' => 'entity:commerce_product',
      ],
      'commerce_product_variation' => [
        'type' => 'entity:commerce_product_variation',
      ],
    ]);
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getDeleteFormRoute($entity_type);
    $route->setDefault('_title_callback', ProductVariationController::class . '::deleteTitle');
    $route->setOption('parameters', [
      'commerce_product' => [
        'type' => 'entity:commerce_product',
      ],
      'commerce_product_variation' => [
        'type' => 'entity:commerce_product_variation',
      ],
    ]);
    $route->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = new Route($entity_type->getLinkTemplate('collection'));
    $route
      ->addDefaults([
        '_entity_list' => 'commerce_product_variation',
        '_title_callback' => ProductVariationController::class . '::collectionTitle',
      ])
      ->setRequirement('_product_variation_collection_access', 'TRUE')
      ->setOption('parameters', [
        'commerce_product' => [
          'type' => 'entity:commerce_product',
        ],
      ])
      ->setOption('_admin_route', TRUE);

    return $route;
  }

}
