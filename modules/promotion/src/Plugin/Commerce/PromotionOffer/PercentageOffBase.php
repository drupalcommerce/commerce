<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the base class for percentage off offers.
 */
abstract class PercentageOffBase extends PromotionOfferBase {

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
      '#type' => 'number',
      '#title' => $this->t('Percentage'),
      '#default_value' => $this->configuration['amount'] * 100,
      '#maxlength' => 255,
      '#required' => TRUE,
      '#step' => 0.1,
      '#min' => 0,
      '#max' => 100,
      '#length' => 4,
      '#field_suffix' => t('%'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['target_plugin_configuration']['amount'])) {
      $form_state->setError($form, $this->t('Percentage amount cannot be empty.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['amount'] = (string) ($values['amount'] / 100);
    parent::submitConfigurationForm($form, $form_state);
  }

}
