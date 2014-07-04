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
   * @param \Drupal\commerce_product\CommerceProductTypeInterface $commerce_product_type
   *
   * @return string
   */
  public function productTypeLoad(CommerceProductTypeInterface $commerce_product_type) {
    return $this->loadObject('commerce_product_type', $commerce_product_type);
  }

  /**
   * Dump devel information for a Commerce Product.
   *
   * @param \Drupal\commerce_product\CommerceProductInterface $commerce_product
   *
   * @return string
   */
  public function productLoad(CommerceProductInterface $commerce_product) {
    return $this->loadObject('commerce_product', $commerce_product);
  }
}
