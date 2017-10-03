<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the coupon redemption pane.
 *
 * @CommerceCheckoutPane(
 *   id = "coupon_redemption",
 *   label = @Translation("Coupon redemption"),
 *   default_step = "_sidebar",
 *   wrapper_element = "container",
 * )
 */
class CouponRedemption extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allow_multiple' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if ($this->configuration['allow_multiple']) {
      $summary = $this->t('Allows multiple coupons: Yes');
    }
    else {
      $summary = $this->t('Allows multiple coupons: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['allow_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple coupons to be redeemed'),
      '#default_value' => $this->configuration['allow_multiple'],
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
      $this->configuration['allow_multiple'] = $values['allow_multiple'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['form'] = [
      '#type' => 'commerce_coupon_redemption_form',
      '#order_id' => $this->order->id(),
      '#cardinality' => $this->configuration['allow_multiple'] ? NULL : 1,
      '#element_ajax' => [
        [get_class($this), 'ajaxRefreshSummary'],
      ],
    ];

    return $pane_form;
  }

  /**
   * Ajax callback for refreshing the order summary.
   */
  public static function ajaxRefreshSummary(array $form, FormStateInterface $form_state) {
    if (isset($form['sidebar']['order_summary'])) {
      $summary_element = $form['sidebar']['order_summary'];
      return new InsertCommand('[data-drupal-selector="edit-sidebar-order-summary"]', $summary_element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The form was submitted with a non-applied coupon in the input field,
    // mapped to a coupon ID in CouponRedemptionForm::validateForm().
    if (!empty($pane_form['form']['code']['#coupon_id'])) {
      $this->order->get('coupons')->appendItem($pane_form['form']['code']['#coupon_id']);
    }
  }

}
