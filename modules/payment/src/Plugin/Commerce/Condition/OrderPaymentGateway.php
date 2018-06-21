<?php

namespace Drupal\commerce_payment\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment gateway condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_payment_gateway",
 *   label = @Translation("Payment gateway"),
 *   display_label = @Translation("Selected payment gateway"),
 *   category = @Translation("Order"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderPaymentGateway extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'payment_gateways' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['payment_gateways'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Payment gateways'),
      '#default_value' => $this->configuration['payment_gateways'],
      '#target_type' => 'commerce_payment_gateway',
      '#hide_single_entity' => FALSE,
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
    $this->configuration['payment_gateways'] = $values['payment_gateways'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    if ($order->get('payment_gateway')->isEmpty()) {
      // The payment gateway is not known yet, the condition cannot pass.
      return FALSE;
    }
    // Avoiding ->target_id to allow the condition to be unit tested,
    // because Prophecy doesn't support magic properties.
    $payment_gateway_item = $order->get('payment_gateway')->first()->getValue();
    $payment_gateway_id = $payment_gateway_item['target_id'];

    return in_array($payment_gateway_id, $this->configuration['payment_gateways']);
  }

}
