<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Controller\ProductController.
 */

namespace Drupal\commerce_product\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\commerce_product\Entity\ProductInterface;

/**
 * Gets responses for Commerce Product routes.
 */
class ProductController extends ControllerBase {

  /**
   * The _title_callback for the entity.commerce_product.edit_form route
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
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
   * @param \Drupal\commerce_product\Entity\ProductInterface $commerce_product
   *   The current product.
   *
   * @return string
   *   The page title
   */
  public function viewProductTitle(ProductInterface $commerce_product) {
    return \Drupal\Component\Utility\Xss::filter($commerce_product->label());
  }

}
