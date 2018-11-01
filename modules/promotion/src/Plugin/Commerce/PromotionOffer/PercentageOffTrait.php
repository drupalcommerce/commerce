<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer;

use Drupal\commerce_price\Calculator;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides common configuration for percentage off offers.
 */
trait PercentageOffTrait {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'percentage' => '0',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);

    if (isset($this->configuration['amount'])) {
      // The 'amount' key was renamed to 'percentage' in 2.0-rc2.
      $this->configuration['percentage'] = $this->configuration['amount'];
      unset($this->configuration['amount']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['percentage'] = [
      '#type' => 'commerce_number',
      '#title' => $this->t('Percentage off'),
      '#default_value' => Calculator::multiply($this->getPercentage(), '100'),
      '#maxlength' => 255,
      '#min' => 0,
      '#max' => 100,
      '#size' => 4,
      '#field_suffix' => $this->t('%'),
      '#required' => TRUE,
      '#weight' => -1,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValue($form['#parents']);
    if (empty($values['percentage'])) {
      $form_state->setError($form, $this->t('Percentage must be a positive number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['percentage'] = (string) ($values['percentage'] / 100);
    }
  }

  /**
   * Gets the percentage.
   *
   * @return string
   *   The percentage.
   */
  protected function getPercentage() {
    return (string) $this->configuration['percentage'];
  }

}
