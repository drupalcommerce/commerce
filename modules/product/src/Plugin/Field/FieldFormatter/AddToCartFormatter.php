<?php

namespace Drupal\commerce_product\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'commerce_add_to_cart' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_add_to_cart",
 *   label = @Translation("Add to cart form"),
 *   field_types = {
 *     "entity_reference",
 *   },
 * )
 */
class AddToCartFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'combine' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['combine'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Combine order items containing the same product variation.'),
      '#description' => $this->t('The order item type, referenced product variation, and data from fields exposed on the Add to Cart form must all match to combine.'),
      '#default_value' => $this->getSetting('combine'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('combine')) {
      $summary[] = $this->t('Combine order items containing the same product variation.');
    }
    else {
      $summary[] = $this->t('Do not combine order items containing the same product variation.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $product = $items->getEntity();
    if (!empty($product->in_preview)) {
      $elements[0]['add_to_cart_form'] = [
        '#type' => 'actions',
        ['#type' => 'button', '#value' => $this->t('Add to cart')],
      ];
      return $elements;
    }
    if ($product->isNew()) {
      return [];
    }

    $view_mode = $this->viewMode;
    // If the field formatter is rendered in Layout Builder, the `viewMode`
    // property will be `_custom` and the original view mode is stored in the
    // third party settings.
    // @see \Drupal\layout_builder\Plugin\Block\FieldBlock::build
    if (isset($this->thirdPartySettings['layout_builder'])) {
      $view_mode = $this->thirdPartySettings['layout_builder']['view_mode'];
    }

    $elements[0]['add_to_cart_form'] = [
      '#lazy_builder' => [
        'commerce_product.lazy_builders:addToCartForm', [
          $product->id(),
          $view_mode,
          $this->getSetting('combine'),
          $langcode,
        ],
      ],
      '#create_placeholder' => TRUE,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $has_cart = \Drupal::moduleHandler()->moduleExists('commerce_cart');
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $has_cart && $entity_type == 'commerce_product' && $field_name == 'variations';
  }

}
