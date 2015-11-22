<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\ProductVariationInlineForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for product variations.
 */
class ProductVariationInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function labels() {
    $labels = [
      'singular' => t('variation'),
      'plural' => t('variations'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function tableFields($bundles) {
    $fields = parent::tableFields($bundles);
    $fields['label']['label'] = t('Title');
    $fields['status'] = [
      'type' => 'field',
      'label' => t('Status'),
      'weight' => 100,
      'display_options' => [
        'settings' => [
          'format' => 'custom',
          'format_custom_true' => t('Active'),
          'format_custom_false' => t('Inactive'),
        ],
      ],
    ];

    return $fields;
  }

}
