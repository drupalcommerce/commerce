<?php

namespace Drupal\commerce_promotion\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_promotion\Entity\CouponInterface;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the billing information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "coupon_redemption",
 *   label = @Translation("Coupon redemption"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class CouponRedemption extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'multiple_coupons' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if ($this->configuration['multiple_coupons']) {
      $summary = $this->t('Multiple coupons: Yes');
    }
    else {
      $summary = $this->t('Multiple coupons: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['multiple_coupons'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple coupons'),
      '#description' => $this->t('If enabled, multiple coupons can be redeemed at checkout.'),
      '#default_value' => $this->configuration['multiple_coupons'],
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
      $this->configuration['multiple_coupons'] = $values['multiple_coupons'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['coupons'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Coupons'),
    ];
    $pane_form['coupons']['redemption'] = [
      '#type' => 'commerce_coupon_redemption_form',
      '#order_id' => $this->order->id(),
      '#multiple_coupons' => $this->configuration['multiple_coupons'],
      '#element_ajax' => [
        [get_class($this), 'ajaxRefreshSummary'],
      ],
    ];

    return $pane_form;
  }

  /**
   * Ajax callback invoked by coupon redemption form.
   */
  public static function ajaxRefreshSummary(array $form, FormStateInterface $form_state) {
    // To refresh the order summary.
    if (isset($form['sidebar']['order_summary'])) {
      $summary_element = $form['sidebar']['order_summary'];
      return new InsertCommand('[data-drupal-selector="edit-sidebar-order-summary"]', $summary_element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $coupon = $form_state->getValue('coupon_redemption');
    if ($coupon instanceof CouponInterface) {
      $this->order->get('coupons')->appendItem($coupon);
      drupal_set_message($this->configuration['submit_message']);
    }
  }

}
