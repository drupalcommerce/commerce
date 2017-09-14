<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager) {
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
    $this->populateCompletionMessagesWithAnonymousMessage();
    $this->populateCompletionMessagesWithAuthenticatedMessage();

    $this->allowOthersToModifyMessages();
  }

  /**
   * Gets the default completion message.
   */
  private function populateCompletionMessagesWithAnonymousMessage() {
    if (\Drupal::currentUser()->isAnonymous()) {
      $this->completionMessags->addMessage($this->t('Your order number is @number.', ['@number' => $this->order->id()]));
      $this->completionMessags->addMessage($this->t('You can view your order on your account page when logged in.'));
    }
  }

  /**
   * Populate the completion messages with the logged in message.
   */
  private function populateCompletionMessagesWithAuthenticatedMessage() {
    if (\Drupal::currentUser()->isAuthenticated()) {
      $this->completionMessags->addMessage(
        $this->t(
          'Your order number is %order_number_with_link.',
          ['%order_number_with_link' => $this->order->toLink($this->order->id())]
        )
      );
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
