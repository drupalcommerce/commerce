<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;

/**
 * Provides the review pane.
 *
 * @CommerceCheckoutPane(
 *   id = "review",
 *   label = @Translation("Review"),
 *   default_step = "review",
 * )
 */
class Review extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface[] $enabled_panes */
    $enabled_panes = array_filter($this->checkoutFlow->getPanes(), function ($pane) {
      return $pane->getStepId() != '_disabled';
    });
    foreach ($enabled_panes as $pane_id => $pane) {
      if ($summary = $pane->buildPaneSummary()) {
        $label = $pane->getLabel();
        if ($pane->isVisible()) {
          $edit_link = Link::createFromRoute($this->t('Edit'), 'commerce_checkout.form', [
            'commerce_order' => $this->order->id(),
            'step' => $pane->getStepId(),
          ]);
          $label .= ' (' . $edit_link->toString() . ')';
        }
        $pane_form[$pane_id] = [
          '#type' => 'fieldset',
          '#title' => $label,
        ];
        $pane_form[$pane_id]['summary'] = [
          '#markup' => $summary,
        ];
      }
    }

    return $pane_form;
  }

}
