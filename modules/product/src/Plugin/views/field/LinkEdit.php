<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Plugin\views\field\LinkEdit.
 */

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\commerce_product\Plugin\views\field\Link;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link commerce product edit.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_product_link_edit")
 */
class LinkEdit extends Link {

  /**
   * Prepares the link to the commerce_product.
   *
   * @param \Drupal\Core\Entity\EntityInterface $commerce_product
   *   The commerce product entity this field belongs to.
   * @param ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($commerce_product, ResultRow $values) {
    // Ensure user has access to edit this commerce product.
    if (!$commerce_product->access('update')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = "product/" . $commerce_product->id() . "/edit";
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Edit');
    return $text;
  }

}
