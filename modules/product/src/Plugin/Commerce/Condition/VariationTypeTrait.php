<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common configuration for the product variation type conditions.
 */
trait VariationTypeTrait {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'variation_types' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['variation_types'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Product variation types'),
      '#default_value' => $this->configuration['variation_types'],
      '#target_type' => 'commerce_product_variation_type',
      '#hide_single_entity' => FALSE,
      '#autocomplete_threshold' => 10,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['variation_types'] = $values['variation_types'];
  }

}
