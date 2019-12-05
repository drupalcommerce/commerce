<?php

namespace Drupal\commerce_product;

use Drupal\commerce\CommerceEntityViewsData;

/**
 * Provides views data for product variations.
 */
class ProductVariationViewsData extends CommerceEntityViewsData {

  /**
   * {@inheritdoc}
   */
  protected function addEntityLinks(array &$data) {
    parent::addEntityLinks($data);

    $t_arguments = ['@entity_type_label' => $this->entityType->getLabel()];
    // EntityViewsData::addEntityLinks() doesn't generate this field
    // because product variations don't have a canonical link template.
    $data['view_commerce_product_variation']['field'] = [
      'title' => $this->t('Link to @entity_type_label', $t_arguments),
      'help' => $this->t('Provide a view link to the @entity_type_label.', $t_arguments),
      'id' => 'commerce_product_variation_view_link',
    ];
  }

}
