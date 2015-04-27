<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Controller\ProductController.
 */

namespace Drupal\commerce_product\Controller;

use Drupal\commerce_product\ProductInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Returns responses for Commerce Product routes.
 */
class ProductController extends ControllerBase {

  /**
   * The _title_callback for the entity.commerce_product.edit_form route
   *
   * @param \Drupal\commerce_product\ProductInterface $commerce_product
   *   The current product.
   *
   * @return string
   *   The page title
   */
  public function editPageTitle(ProductInterface $commerce_product) {
    return $this->t('Editing @label', ['@label' => $commerce_product->label()]);
  }

  /**
   * The _title_callback for the entity.commerce_product.canonical route
   *
   * @param \Drupal\commerce_product\ProductInterface $commerce_product
   *   The current product.
   *
   * @return string
   *   The page title
   */
  public function viewProductTitle(ProductInterface $commerce_product) {
    return \Drupal\Component\Utility\Xss::filter($commerce_product->label());
  }

  /**
   * Provides the add form for an entity of a specific bundle.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return array
   *   The add form.
   */
  public function addForm(RouteMatchInterface $routeMatch) {
    $bundle = $this->getBundleFromRouteMatch($routeMatch);
    $entityType = $bundle->getEntityType()->getBundleOf();
    $bundleKey = $this->entityManager()
      ->getDefinition($entityType)
      ->getKey('bundle');
    $product = $this->entityManager()->getStorage($entityType)->create(
      [$bundleKey => $bundle->id()]
    );
    if ($routeMatch->getParameters()->get('commerce_store')) {
      $product->setStoreId($routeMatch->getParameters()->get('commerce_store'));
    }
    return $this->entityFormBuilder()->getForm($product, 'add');
  }


  /**
   * Returns the bundle object from the route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The bundle object as determined from the passed-in route match.
   */
  protected function getBundleFromRouteMatch(RouteMatchInterface $routeMatch) {
    // Assume that the bundle is the last route parameter.
    return $routeMatch->getParameters()->get('commerce_product_type');
  }

  /**
   * Returns the bundle object from the route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The bundle object as determined from the passed-in route match.
   */
  protected function getStoreFromRouteMatch(RouteMatchInterface $routeMatch) {
    // Assume that the bundle is the last route parameter.
    return $routeMatch->getParameters()->get('store');
  }


  /**
   * The title callback for the add form page.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   *
   * @return string
   *   The page title.
   */
  public function addFormTitle(RouteMatchInterface $routeMatch) {
    $bundle = $this->getBundleFromRouteMatch($routeMatch);

    return $this->t('Create @label', ['@label' => $bundle->label()]);
  }
}
