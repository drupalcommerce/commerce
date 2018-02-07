<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the payment information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_information",
 *   label = @Translation("Payment information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class PaymentInformation extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = [];
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->get('payment_gateway')->entity;
    if (!$payment_gateway) {
      return $summary;
    }

    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $payment_method = $this->order->get('payment_method')->entity;
    if ($payment_gateway_plugin instanceof SupportsStoredPaymentMethodsInterface && $payment_method) {
      $view_builder = $this->entityTypeManager->getViewBuilder('commerce_payment_method');
      $summary = $view_builder->view($payment_method, 'default');
    }
    else {
      $billing_profile = $this->order->getBillingProfile();
      if ($billing_profile) {
        $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
        $profile_view = $profile_view_builder->view($billing_profile, 'default');
        $label = $payment_gateway->getPlugin()->getDisplayLabel();
        $summary = [
          'label' => [
            '#markup' => $label,
          ],
          'profile' => $profile_view,
        ];
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    // Load the payment gateways. This fires an event for filtering the
    // available gateways, and then evaluates conditions on all remaining ones.
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($this->order);
    // Can't proceed without any payment gateways.
    if (empty($payment_gateways)) {
      drupal_set_message($this->noPaymentGatewayErrorMessage(), 'error');
      return [];
    }

    $options = $this->buildPaymentMethodOptions($payment_gateways);
    $user_input = $form_state->getUserInput();
    $values = NestedArray::getValue($user_input, $pane_form['#parents']);
    $default_option = NULL;
    if (!empty($values['payment_method'])) {
      // The form was rebuilt via AJAX, use the submitted value.
      $default_option = $values['payment_method'];
    }
    else {
      $default_option = $this->getDefaultPaymentMethodOption($options);
    }

    // Prepare the form for ajax.
    $pane_form['#wrapper_id'] = Html::getUniqueId('payment-information-wrapper');
    $pane_form['#prefix'] = '<div id="' . $pane_form['#wrapper_id'] . '">';
    $pane_form['#suffix'] = '</div>';
    // Core bug #1988968 doesn't allow the payment method add form JS to depend
    // on an external library, so the libraries need to be preloaded here.
    foreach ($payment_gateways as $payment_gateway) {
      if ($js_library = $payment_gateway->getPlugin()->getJsLibrary()) {
        $pane_form['#attached']['library'][] = $js_library;
      }
    }

    $pane_form['payment_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment method'),
      '#options' => array_column($options, 'label', 'id'),
      '#default_value' => $default_option,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $pane_form['#wrapper_id'],
      ],
      '#access' => count($options) > 1,
    ];
    // Store the values for submitPaneForm().
    foreach ($options as $option_id => $option) {
      $pane_form['payment_method'][$option_id]['#payment_gateway'] = $option['payment_gateway'];
      if (isset($option['payment_method'])) {
        $pane_form['payment_method'][$option_id]['#payment_method'] = $option['payment_method'];
      }
      if (isset($option['payment_method_type'])) {
        $pane_form['payment_method'][$option_id]['#payment_method_type'] = $option['payment_method_type'];
      }
    }

    $selected_option = $pane_form['payment_method'][$default_option];
    $payment_gateway = $payment_gateways[$selected_option['#payment_gateway']];

    // Add details markup if available.
    if (empty($selected_option['#payment_method']) && $details = $payment_gateway->getPlugin()->getDetails()) {
      $pane_form['details'] = [
        '#type' => 'item',
        '#markup' => check_markup($details['value'], $details['format']),
      ];
    }

    if ($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface) {
      if (!empty($selected_option['#payment_method_type'])) {
        /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
        $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');
        $payment_method = $payment_method_storage->create([
          'type' => $selected_option['#payment_method_type'],
          'payment_gateway' => $selected_option['#payment_gateway'],
          'uid' => $this->order->getCustomerId(),
        ]);

        $pane_form['add_payment_method'] = [
          '#type' => 'commerce_payment_gateway_form',
          '#operation' => 'add-payment-method',
          '#default_value' => $payment_method,
        ];
      }
    }
    else {
      $store = $this->order->getStore();
      $billing_profile = $this->order->getBillingProfile();
      if (!$billing_profile) {
        $billing_profile = $this->entityTypeManager->getStorage('profile')->create([
          'uid' => $this->order->getCustomerId(),
          'type' => 'customer',
        ]);
      }

      $pane_form['billing_information'] = [
        '#type' => 'commerce_profile_select',
        '#default_value' => $billing_profile,
        '#default_country' => $store->getAddress()->getCountryCode(),
        '#available_countries' => $store->getBillingCountries(),
      ];
    }

    return $pane_form;
  }

  /**
   * Builds the payment method options for the given payment gateways.
   *
   * The payment method options will be derived from the given payment gateways
   * and added to the return array in the following order:
   * 1) The customer's stored payment methods.
   * 2) The order's payment method (if not added in the previous step).
   * 3) Options to create new payment methods of valid types.
   * 4) Options for the remaining gateways (off-site, manual, etc).
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways
   *   The payment gateways.
   *
   * @return array
   *   The options array keyed by payment method ID (or in the case of the new
   *   payment method options, a key indicating the type of payment method to
   *   create) whose values are associative arrays with the following keys:
   *   - id: the payment method ID (or new payment method key).
   *   - label: the label to use for selecting this payment method.
   *   - payment_gateway: the ID of the gateway the payment method is for.
   *   - payment_method: the ID of an existing stored payment method.
   *   - payment_method_type: the payment method type ID for new payment methods
   */
  protected function buildPaymentMethodOptions(array $payment_gateways) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways_with_payment_methods */
    $payment_gateways_with_payment_methods = array_filter($payment_gateways, function ($payment_gateway) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      return $payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface;
    });

    $options = [];
    // 1) Add options to reuse stored payment methods for known customers.
    $customer = $this->order->getCustomer();
    if ($customer) {
      $billing_countries = $this->order->getStore()->getBillingCountries();
      /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
      $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');

      foreach ($payment_gateways_with_payment_methods as $payment_gateway_id => $payment_gateway) {
        $payment_methods = $payment_method_storage->loadReusable($customer, $payment_gateway, $billing_countries);

        foreach ($payment_methods as $payment_method_id => $payment_method) {
          $options[$payment_method_id] = [
            'id' => $payment_method_id,
            'label' => $payment_method->label(),
            'payment_gateway' => $payment_gateway_id,
            'payment_method' => $payment_method_id,
          ];
        }
      }
    }

    // 2) Add the order's payment method if it was not included above.
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $order_payment_method */
    $order_payment_method = $this->order->get('payment_method')->entity;
    if ($order_payment_method) {
      $order_payment_method_id = $order_payment_method->id();

      if (!isset($options[$order_payment_method_id])) {
        $options[$order_payment_method_id] = [
          'id' => $order_payment_method_id,
          'label' => $order_payment_method->label(),
          'payment_gateway' => $order_payment_method->getPaymentGatewayId(),
          'payment_method' => $order_payment_method_id,
        ];
      }
    }

    // 3) Add options to create new stored payment methods of supported types.
    $payment_method_type_counts = [];
    // Count how many new payment method options will be built per gateway.
    foreach ($payment_gateways_with_payment_methods as $payment_gateway) {
      $payment_method_types = $payment_gateway->getPlugin()->getPaymentMethodTypes();

      foreach ($payment_method_types as $payment_method_type_id => $payment_method_type) {
        if (!isset($payment_method_type_counts[$payment_method_type_id])) {
          $payment_method_type_counts[$payment_method_type_id] = 1;
        }
        else {
          $payment_method_type_counts[$payment_method_type_id]++;
        }
      }
    }

    foreach ($payment_gateways_with_payment_methods as $payment_gateway) {
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      $payment_method_types = $payment_gateway_plugin->getPaymentMethodTypes();

      foreach ($payment_method_types as $payment_method_type_id => $payment_method_type) {
        $option_id = 'new--' . $payment_method_type_id . '--' . $payment_gateway->id();
        $option_label = $payment_method_type->getCreateLabel();
        // If there is more than one option for this payment method type,
        // append the payment gateway label to avoid duplicate option labels.
        if ($payment_method_type_counts[$payment_method_type_id] > 1) {
          $option_label = $this->t('@payment_method_label (@payment_gateway_label)', [
            '@payment_method_label' => $payment_method_type->getCreateLabel(),
            '@payment_gateway_label' => $payment_gateway_plugin->getDisplayLabel(),
          ]);
        }

        $options[$option_id] = [
          'id' => $option_id,
          'label' => $option_label,
          'payment_gateway' => $payment_gateway->id(),
          'payment_method_type' => $payment_method_type_id,
        ];
      }
    }

    // 4) Add options for the remaining gateways (off-site, manual, etc).
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $other_payment_gateways */
    $other_payment_gateways = array_diff_key($payment_gateways, $payment_gateways_with_payment_methods);
    foreach ($other_payment_gateways as $payment_gateway_id => $payment_gateway) {
      $options[$payment_gateway_id] = [
        'id' => $payment_gateway_id,
        'label' => $payment_gateway->getPlugin()->getDisplayLabel(),
        'payment_gateway' => $payment_gateway_id,
      ];
    }

    return $options;
  }

  /**
   * Gets the default payment method option.
   *
   * Priority:
   * 1) The order's payment method
   * 2) The order's payment gateway (if it does not support payment methods)
   * 3) First defined option.
   *
   * @param array $options
   *   The options.
   *
   * @return string
   *   The selected option ID.
   */
  protected function getDefaultPaymentMethodOption(array $options) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $order_payment_gateway */
    $order_payment_gateway = $this->order->get('payment_gateway')->entity;
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $order_payment_method */
    $order_payment_method = $this->order->get('payment_method')->entity;

    $default_option = NULL;
    if ($order_payment_method) {
      $default_option = $order_payment_method->id();
    }
    elseif ($order_payment_gateway && !($order_payment_gateway instanceof SupportsStoredPaymentMethodsInterface)) {
      $default_option = $order_payment_gateway->id();
    }
    // The order doesn't have a payment method/gateway specified, or it has, but it is no longer available.
    if (!$default_option || !isset($options[$default_option])) {
      $option_ids = array_keys($options);
      $default_option = reset($option_ids);
    }

    return $default_option;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);

    if (!isset($values['payment_method'])) {
      $form_state->setError($complete_form, $this->noPaymentGatewayErrorMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $selected_option = $pane_form['payment_method'][$values['payment_method']];
    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $payment_gateway_storage->load($selected_option['#payment_gateway']);
    if (!$payment_gateway) {
      return;
    }

    if ($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface) {
      if (!empty($selected_option['#payment_method_type'])) {
        // The payment method was just created.
        $payment_method = $values['add_payment_method'];
      }
      else {
        /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
        $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');
        $payment_method = $payment_method_storage->load($selected_option['#payment_method']);
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $this->order->set('payment_gateway', $payment_method->getPaymentGateway());
      $this->order->set('payment_method', $payment_method);
      $this->order->setBillingProfile($payment_method->getBillingProfile());
    }
    else {
      $this->order->set('payment_gateway', $payment_gateway);
      $this->order->set('payment_method', NULL);
      $this->order->setBillingProfile($pane_form['billing_information']['#profile']);
    }
  }

  /**
   * Returns an error message in case there are no payment gateways.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The error message.
   */
  protected function noPaymentGatewayErrorMessage() {
    return $this->t('No payment gateways are defined, create one first.');
  }

}
