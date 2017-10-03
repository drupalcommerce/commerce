<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the billing information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "billing_information",
 *   label = @Translation("Billing information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class BillingInformation extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = [];
    if ($billing_profile = $this->order->getBillingProfile()) {
      $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
      $summary = $profile_view_builder->view($billing_profile, 'default');
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $store = $this->order->getStore();
    $pane_form['profile'] = [
      '#type' => 'commerce_profile_select',
      '#title' => $this->t('Select an address'),
      '#default_value' => $this->order->getBillingProfile(),
      '#default_country' => $store->getAddress()->getCountryCode(),
      '#available_countries' => $store->getBillingCountries(),
      '#profile_type' => 'customer',
      '#owner_uid' => $this->order->getCustomerId(),
    ];

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $this->order->setBillingProfile($values['profile']);
  }

}
