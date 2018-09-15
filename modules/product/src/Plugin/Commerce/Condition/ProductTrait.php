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
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

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
    $product_ids = $this->getProductIds();
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

    // Convert selected IDs into UUIDs, and store them.
    $values = $form_state->getValue($form['#parents']);
    $product_ids = array_column($values['products'], 'target_id');
    $product_uuids = $this->entityUuidMapper->mapFromIds('commerce_product', $product_ids);
    $this->configuration['products'] = [];
    foreach ($product_uuids as $uuid) {
      $this->configuration['products'][] = [
        'product' => $uuid,
      ];
    }
  }

  /**
   * Gets the configured product IDs.
   *
   * @return array
   *   The product IDs.
   */
  protected function getProductIds() {
    $product_ids = array_column($this->configuration['products'], 'product_id');
    if (!empty($product_ids)) {
      // Legacy configuration found, with explicit product IDs.
      return $product_ids;
    }
    else {
      // Map the UUIDs.
      $product_uuids = array_column($this->configuration['products'], 'product');
      return $this->entityUuidMapper->mapToIds('commerce_product', $product_uuids);
    }
  }

}
