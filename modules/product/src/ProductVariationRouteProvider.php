<?php

namespace Drupal\commerce_product;

use Drupal\commerce_product\Controller\ProductVariationController;
use Drupal\entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for the product variation entity.
 */
class ProductVariationRouteProvider extends AdminHtmlRouteProvider {

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
