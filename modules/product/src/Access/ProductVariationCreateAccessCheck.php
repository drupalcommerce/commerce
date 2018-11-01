<?php

namespace Drupal\commerce_product\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines an access checker for product variation creation.
 *
 * Takes the product variation type ID from the product type, since a product
 * is always present in variation routes.
 */
class ProductVariationCreateAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductVariationCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks access to create the product variation.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $product = $route_match->getParameter('commerce_product');
    if (!$product) {
      return AccessResult::forbidden();
    }
    $access_control_handler = $this->entityTypeManager->getAccessControlHandler('commerce_product_variation');
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    /** @var \Drupal\commerce_product\Entity\ProductTypeInterface $product_type */
    $product_type = $product_type_storage->load($product->bundle());
    $variation_type_id = $product_type->getVariationTypeId();

    return $access_control_handler->createAccess($variation_type_id, $account, [], TRUE);
  }

}
