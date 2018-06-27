<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common configuration for the product conditions.
 */
trait ProductTrait {

  /**
   * The product storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productStorage;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'products' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $products = NULL;
    $product_ids = array_column($this->configuration['products'], 'product_id');
    if (!empty($product_ids)) {
      $products = $this->productStorage->loadMultiple($product_ids);
    }
    $form['products'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Products'),
      '#default_value' => $products,
      '#target_type' => 'commerce_product',
      '#tags' => TRUE,
      '#required' => TRUE,
      '#maxlength' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['products'] = [];
    foreach ($values['products'] as $value) {
      $this->configuration['products'][] = [
        'product_id' => $value['target_id'],
      ];
    }
  }

}
