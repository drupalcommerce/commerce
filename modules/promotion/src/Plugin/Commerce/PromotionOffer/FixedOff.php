<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Fixed off' offer.
 *
 * @CommercePromotionOffer(
 *   id = "commerce_promotion_fixed_off",
 *   label = @Translation("Fixed off"),
 * )
 */
class FixedOff extends PromotionOfferBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'amount' => 0,
    ] + parent::defaultConfiguration();
  }

  /**
   * Gets the percentage amount, as a decimal, negated.
   *
   * @return string
   *   The amount.
   */
  public function getAmount() {
    return (string) $this->configuration['amount'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form += parent::buildConfigurationForm($form, $form_state);

    $form['amount'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Amount'),
      '#default_value' => $this->configuration['amount'],
      '#maxlength' => 255,
      '#required' => TRUE,
      '#min' => 0,
      '#size' => 8,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['target_plugin_configuration']['amount'])) {
      $form_state->setError($form, $this->t('Fixed amount cannot be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['amount'] = $values['amount'];
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    /** @var \Drupal\commerce_order\EntityAdjustableInterface $entity */
    $entity = $this->getTargetEntity();

    // @todo is there a sane way to add a getCurrencyCode to EntityAdjustableInterface.
    // both order item and orders have ::getTotalPrice. Bug in the trenches.
    $currency_code = $entity->getTotalPrice()->getCurrencyCode();
    $adjustment_amount = new Price($this->getAmount(), $currency_code);
    $this->applyAdjustment($entity, $adjustment_amount);
  }
}
