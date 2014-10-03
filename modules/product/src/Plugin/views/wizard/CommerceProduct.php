<?php

/**
 * @file
 * Definition of Drupal\commerce_product\Plugin\views\wizard\CommerceProduct.
 */

namespace Drupal\commerce_product\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * @todo: replace numbers with constants.
 */

/**
 * Tests creating commerce product views with the wizard.
 *
 * @ViewsWizard(
 *   id = "commerce_product",
 *   base_table = "commerce_product",
 *   title = @Translation("Commerce Products")
 * )
 */
class CommerceProduct extends WizardPluginBase {

  /**
   * Set the created column.
   */
  protected $createdColumn = 'created';

  /**
   * Set default values for the path field options.
   */
  protected $pathField = array(
    'id' => 'product_id',
    'table' => 'commerce_product',
    'field' => 'product_id',
    'exclude' => TRUE,
    'link_to_commerce_product' => FALSE,
    'alter' => array(
      'alter_text' => TRUE,
      'text' => 'product/[product_id]'
    ),
  );
}
