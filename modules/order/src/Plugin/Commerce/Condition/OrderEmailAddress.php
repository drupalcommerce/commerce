<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Email address condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_email_address",
 *   label = @Translation("Email address"),
 *   display_label = @Translation("Limit by email address"),
 *   category = @Translation("Customer"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderEmailAddress extends ConditionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'mail' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['email'] = [
      '#type' => 'email',
      '#default_value' => $this->configuration['mail'],
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
    $this->configuration['mail'] = $values['email'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {

    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;
    $customer = $order->getCustomer();
    $roles = $customer ? $customer->getEmail() : ['anonymous'];
    
    if($this->configuration['mail'] == $customer->getEmail() ) {
        return true;
    }

    return false; 
  }

}
