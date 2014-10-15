<?php

/**
 * @file
 * Contains \Drupal\commerce_product\CommerceProductViewsData.
 */

namespace Drupal\commerce_product;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the product entity type.
 */
class CommerceProductViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['commerce_product']['view_product'] = array(
      'field' => array(
        'title' => t('Link'),
        'help' => t('Provide a simple link to the product.'),
        'id' => 'commerce_product_link',
      ),
    );

    $data['commerce_product']['edit_product'] = array(
      'field' => array(
        'title' => t('Edit Link'),
        'help' => t('Provide a simple link to edit the product.'),
        'id' => 'commerce_product_link_edit',
      ),
    );

    $data['commerce_product']['delete_product'] = array(
      'field' => array(
        'title' => t('Delete Link'),
        'help' => t('Provide a simple link to delete the product.'),
        'id' => 'commerce_product_link_delete',
      ),
    );

    return $data;
  }
}