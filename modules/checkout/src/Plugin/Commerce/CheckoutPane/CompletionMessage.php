<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the completion message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "completion_message",
 *   label = @Translation("Completion message"),
 *   default_step = "complete",
 * )
 */
class CompletionMessage extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // @todo Add token, HTML support.
    $default_message = 'Your order number is %order_number. ';
    $default_message .= 'You can view your order on your account page when logged in.';

    return [
      'message' => $default_message,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#default_value' => $this->configuration['message'],
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
      $this->configuration['message'] = $values['message'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $message = $this->configuration['message'];
    $message = str_replace('%order_number', $this->order->getOrderNumber(), $message);

    $pane_form['message'] = [
      '#markup' => $message,
    ];
    return $pane_form;
  }

}
