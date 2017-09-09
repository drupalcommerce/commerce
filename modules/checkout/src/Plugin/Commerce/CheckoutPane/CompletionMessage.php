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
   * @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CompletionMessages
   */
  private $completionMessags;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow, \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow, $entity_type_manager);
    $this->completionMessags = new CompletionMessages();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $this->preparePaneForm();

    $pane_form['#theme'] = 'commerce_checkout_completion_message';
    $pane_form['#order_entity'] = $this->order;
    $pane_form['#completion_messages'] = $this->completionMessags;

    return $pane_form;
  }

  /**
   * Prepares the necessary data for the completion messages.
   */
  public function preparePaneForm() {
    $this->populateCompletionMessages();
  }

  /**
   * Gets the completion messages.
   */
  private function populateCompletionMessages() {
    $this->populateCompletionMessagesWithDefaultMessage();
    $this->populateCompletionMessagesWithLoggedInMessageIfLoggedIn();

    $this->allowOthersToModifyMessages();
  }

  /**
   * Gets the default completion message.
   */
  private function populateCompletionMessagesWithDefaultMessage() {
    return $this->completionMessags->addMessage($this->t('Your order number is @number.', ['@number' => $this->order->id()]));
  }

  /**
   * Populate the completion messages with the logged in message.
   */
  private function populateCompletionMessagesWithLoggedInMessageIfLoggedIn() {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $this->completionMessags->addMessage($this->t('You can view your order on your account page when logged in.'));
    }
  }

  /**
   * Allow other modules to alter the messages.
   */
  private function allowOthersToModifyMessages() {
    \Drupal::moduleHandler()
      ->alter('checkout_completion_messages', $this->completionMessags, $this->order);
  }

}
