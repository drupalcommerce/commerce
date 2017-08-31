<?php

namespace Drupal\commerce_product\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the product variation type condition for order items.
 *
 * @CommerceCondition(
 *   id = "order_item_variation_type",
 *   label = @Translation("Product variation type"),
 *   display_label = @Translation("Limit by product variation type"),
 *   category = @Translation("Product"),
 *   entity_type = "commerce_order_item",
 * )
 */
class OrderItemVariationType extends ConditionBase {

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

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
    $order_item = $entity;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchasable_entity */
    $purchasable_entity = $order_item->getPurchasedEntity();
    if (!$purchasable_entity || $purchasable_entity->getEntityTypeId() != 'commerce_product_variation') {
      return FALSE;
    }

    return in_array($purchasable_entity->bundle(), $this->configuration['variation_types']);
  }

}
