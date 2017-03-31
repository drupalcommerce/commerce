<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Order: product' condition.
 *
 * @CommercePromotionCondition(
 *   id = "commerce_promotion_order_product",
 *   label = @Translation("Product"),
 *   target_entity_type = "commerce_order",
 * )
 */
class OrderProduct extends PromotionConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'products' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * Evaluates the condition and returns TRUE or FALSE accordingly.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  public function evaluate() {
    $product_ids = $this->configuration['products'];
    if (empty($product_ids)) {
      return FALSE;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->getTargetEntity();
    $product_ids = array_flip(array_column($product_ids, 'target_id'));

    foreach ($order->getItems() as $item) {
      if ($item->hasField('purchased_entity') && isset($product_ids[$item->getPurchasedEntityId()])) {
        unset($product_ids[$item->getPurchasedEntityId()]);
      }
    }

    return empty($product_ids);
  }

  /**
   * Provides a human readable summary of the condition's configuration.
   */
  public function summary() {
    return $this->t('Compares order products.');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);

    // The #default_value property has to be an entity object or an
    // array of entity objects.
    if (!empty($this->configuration['products'])) {
      $this->configuration['products'] = \Drupal::entityTypeManager()
        ->getStorage('commerce_product')
        ->loadMultiple(array_column($this->configuration['products'], 'target_id'));
    }

    $form['products'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'commerce_product',
      '#default_value' => $this->configuration['products'],
      '#tags' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

}
