<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Plugin\views\field\LinkDelete.
 */

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\commerce_product\Plugin\views\field\Link;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete a commerce product.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("commerce_product_link_delete")
 */
class LinkDelete extends Link {

  /**
   * Prepares the link to delete a commerce product.
   *
   * @param \Drupal\Core\Entity\EntityInterface $commerce_product
   *   The commerce product entity this field belongs to.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($commerce_product, ResultRow $values) {
    // Ensure user has access to delete this commerce product.
    if (!$commerce_product->access('delete')) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $commerce_product->getSystemPath('delete-form');
    $this->options['alter']['query'] = drupal_get_destination();

    $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Delete');
    return $text;
  }

}
