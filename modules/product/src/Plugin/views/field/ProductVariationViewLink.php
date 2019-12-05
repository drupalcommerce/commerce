<?php

namespace Drupal\commerce_product\Plugin\views\field;

use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to view a product variation.
 *
 * @ViewsField("commerce_product_variation_view_link")
 */
class ProductVariationViewLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    return $this->getEntity($row)->toUrl('canonical')->setAbsolute($this->options['absolute']);
  }

}
