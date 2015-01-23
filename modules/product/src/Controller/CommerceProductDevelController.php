<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Controller\CommerceProductDevelController.
 */

namespace Drupal\commerce_product\Controller;

use Drupal\commerce_product\CommerceProductInterface;
use Drupal\commerce_product\CommerceProductTypeInterface;
use Drupal\devel\Controller\DevelController;

/**
 * Returns responses for Commerce Product devel routes.
 */
class CommerceProductDevelController extends DevelController {

  /**
   * Dump devel information for a Commerce Product Type.
   *
   * @param \Drupal\commerce_product\CommerceProductTypeInterface $commerceProductType
   *
   * @return string
   */
  public function productTypeLoad(CommerceProductTypeInterface $commerceProductType) {
    return $this->loadObject('commerce_product_type', $commerceProductType);
  }

  /**
   * Dump devel information for a Commerce Product.
   *
   * @param \Drupal\commerce_product\CommerceProductInterface $commerceProduct
   *
   * @return string
   */
  public function productLoad(CommerceProductInterface $commerceProduct) {
    return $this->loadObject('commerce_product', $commerceProduct);
  }

}
