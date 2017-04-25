<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the contact information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "contact_information",
 *   label = @Translation("Contact information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class ContactInformation extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'double_entry' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['double_entry'])) {
      $summary = $this->t('Require double entry of email: Yes');
    }
    else {
      $summary = $this->t('Require double entry of email: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['double_entry'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require double entry of email'),
      '#description' => $this->t('Forces anonymous users to enter their email in two consecutive fields, which must have identical values.'),
      '#default_value' => $this->configuration['double_entry'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['double_entry'] = !empty($values['double_entry']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // Show the pane only for guest checkout.
    return empty($this->order->getCustomerId());
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    return [
      '#plain_text' => $this->order->getEmail(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->order->getEmail(),
      '#required' => TRUE,
    ];
    if ($this->configuration['double_entry']) {
      $pane_form['email_confirm'] = [
        '#type' => 'email',
        '#title' => $this->t('Confirm email'),
        '#default_value' => $this->order->getEmail(),
        '#required' => TRUE,
      ];
    }

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    if ($this->configuration['double_entry'] && $values['email'] != $values['email_confirm']) {
      $form_state->setError($pane_form, $this->t('The specified emails do not match.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $this->order->setEmail($values['email']);
  }

}
