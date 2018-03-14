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
      return !in_array($pane->getStepId(), ['_sidebar', '_disabled']);
    });
    foreach ($enabled_panes as $pane_id => $pane) {
      if ($summary = $pane->buildPaneSummary()) {
        // BC layer for panes which still return rendered strings.
        if ($summary && !is_array($summary)) {
          $summary = [
            '#markup' => $summary,
          ];
        }

        $label = isset($summary['#title']) ? $summary['#title'] : $pane->getDisplayLabel();
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
        $pane_form[$pane_id]['summary'] = $summary;
      }
    }

    return $pane_form;
  }

}
