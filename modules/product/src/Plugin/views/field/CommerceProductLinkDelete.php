<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Plugin\views\field\CommerceProductLink.
 */

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\commerce_product\Plugin\views\field\CommerceProductLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete a product.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_product_link_delete")
 */
class CommerceProductLinkDelete extends CommerceProductLink {

  /**
   * Prepares the link to the product.
   *
   * @param \Drupal\commerce_product\Entity\CommerceProductInterface $product
   *   The product entity this field belongs to.
   * @param ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($product, ResultRow $values) {
    if ($product->access('delete')) {
      $this->options['alter']['make_link'] = TRUE;
      $this->options['alter']['path'] = 'product/' . $product->id() . '/delete';
      $this->options['alter']['query'] = drupal_get_destination();

      $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Delete');
      return $text;
    }
  }
}

