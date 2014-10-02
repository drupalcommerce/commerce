<?php

/**
 * @file
 * Contains \Drupal\commerce_product\CommerceProductViewsData.
 */

namespace Drupal\commerce_product;

use Drupal\views\EntityViewsDataInterface;

/**
 * Provides the views data for the commerce_product entity type.
 */
class CommerceProductViewsData implements EntityViewsDataInterface {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {

    // Define the base group of this table. Fields that don't have a group defined
    // will go into this field by default.
    $data['commerce_product']['table']['group'] = t('Product');

    $data['commerce_product']['table']['base'] = array(
      'field' => 'product_id',
      'title' => t('Commerce Product'),
      'help' => t('Products from the store.'),
      'access query tag' => 'commerce_product_access',
    );
    $data['commerce_product']['table']['entity type'] = 'commerce_product';
    $data['commerce_product']['table']['wizard_id'] = 'commerce_product';

    $data['commerce_product_field_data']['table']['group'] = t('Product');
    $data['commerce_product_field_data']['table']['entity type'] = 'commerce_product';
    $data['commerce_product_field_data']['table']['join']['commerce_product'] = array(
      'type' => 'INNER',
      'left_field' => 'product_id',
      'field' => 'product_id',
    );

    $data['commerce_product_field_data']['type'] = array(
      'title' => t('Type'),
      'help' => t('The human-readable name of the type of the product.'),
      'field' => array(
        'id' => 'commerce_product_type'
      ),
      'argument' => array(
        'id' => 'commerce_product_type',
      )
    );

    $data['commerce_product_field_data']['sku'] = array(
      'title' => t('SKU'),
      'help' => t('The unique human-readable identifier of the product.'),
      'field' => array(
        'id' => 'commerce_product',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'argument' => array(
        'id' => 'string',
      ),
    );

    $data['commerce_product_field_data']['title'] = array(
      'title' => t('Title'),
      'help' => t('The title of the product used for administrative display.'),
      'field' => array(
        'id' => 'commerce_product',
      ),
      'filter' => array(
        'id' => 'string',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'argument' => array(
        'id' => 'string',
      ),
    );

    $data['commerce_product']['product_id'] = array(
      'title' => t('Product ID'),
      'help' => t('The unique internal identifier of the product.'),
      'field' => array(
        'id' => 'commerce_product',
      ),
      'filter' => array(
        'id' => 'numeric',
      ),
      'sort' => array(
        'id' => 'standard',
      ),
      'argument' => array(
        'id' => 'numeric',
      )
    );

    $data['node']['view_commerce_product'] = array(
      'field' => array(
        'title' => t('Link'),
        'help' => t('Provide a simple link to the administrator view of the product.'),
        'id' => 'node_link',
      ),
    );

    $data['commerce_product']['edit_commerce_product'] = array(
      'field' => array(
        'title' => t('Edit link'),
        'help' => t('Provide a simple link to edit the product.'),
        'id' => 'commerce_product_link_edit',
      ),
    );


    $data['commerce_product']['delete_commerce_product'] = array(
      'field' => array(
        'title' => t('Delete link'),
        'help' => t('Provide a simple link to delete the product.'),
        'id' => 'commerce_product_link_delete',
      ),
    );

    return $data;
  }

}
