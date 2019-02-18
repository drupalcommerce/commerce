<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Email address condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_email",
 *   label = @Translation("Customer email"),
 *   category = @Translation("Customer"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderEmail extends ConditionBase {

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

    $form['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
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
    $this->configuration['mail'] = $values['mail'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;

    return strcasecmp($this->configuration['mail'], $order->getEmail()) === 0;
  }

}
