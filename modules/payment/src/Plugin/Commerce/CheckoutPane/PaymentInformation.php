<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\profile\Entity\Profile;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class PaymentInformation extends CheckoutPaneBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The payment gateway storage.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayStorageInterface
   */
  protected $paymentGatewayStorage;

  /**
   * The payment method storage.
   *
   * @var \Drupal\commerce_payment\PaymentMethodStorageInterface
   */
  protected $paymentMethodStorage;

  /**
   * Constructs a new PaymentInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->entityTypeManager = $entity_type_manager;
    $this->paymentGatewayStorage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $this->paymentMethodStorage = $this->entityTypeManager->getStorage('commerce_payment_method');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = [];
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    if (!$payment_gateway) {
      return $summary;
    }

    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $payment_method = $this->order->payment_method->entity;
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
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways */
    $payment_gateways = $this->paymentGatewayStorage->loadMultipleForOrder($this->order);
    // When no payment gateways are defined, throw an error and fail reliably.
    if (empty($payment_gateways)) {
      drupal_set_message($this->noPaymentGatewayErrorMessage(), 'error');
      return [];
    }

    $options = [];
    $default_option = NULL;
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $order_payment_method */
    $order_payment_method = $this->order->payment_method->entity;
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $order_payment_gateway */
    $order_payment_gateway = $this->order->payment_gateway->entity;
    $customer = $this->order->getCustomer();
    foreach ($payment_gateways as $payment_gateway) {
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      // Add existing payment methods for customer.
      if ($customer) {
        $payment_methods = $this->paymentMethodStorage->loadReusable($customer, $payment_gateway);
        foreach ($payment_methods as $payment_method) {
          $option_id = $payment_method->id();
          $options[$option_id] = [
            'id' => $option_id,
            'label' => $payment_method->label(),
            'payment_method_plugin' => $payment_gateway_plugin->getPluginId(),
            'payment_gateway' => $payment_gateway->id(),
          ];
        }
      }
      // The order's payment method must always be available.
      if ($order_payment_method && !isset($options[$order_payment_method->id()])) {
        $option_id = $order_payment_method->id();
        $options[$option_id] = [
          'id' => $option_id,
          'label' => $order_payment_method->label(),
          'payment_method_plugin' => $order_payment_method->getType()->getPluginId(),
          'payment_gateway' => $order_payment_gateway->id(),
        ];
      }
      // New payment method.
      $payment_method_types = $payment_gateway_plugin->getPaymentMethodTypes();
      foreach ($payment_method_types as $payment_method_type) {
        $option_id = 'new--' . $payment_method_type->getPluginId() . '--' . $payment_gateway->id();
        $option_label = $this->t('@payement_method_label: @payment_gateway_label', [
          '@payement_method_label' => $payment_method_type->getCreateLabel(),
          '@payment_gateway_label' => $payment_gateway->label(),
        ]);
        $options[$option_id] = [
          'id' => $option_id,
          'label' => $option_label,
          'payment_method_plugin' => $payment_method_type->getPluginId(),
          'payment_gateway' => $payment_gateway->id(),
        ];
      }
    }

    // Get the default value.
    $user_input = $form_state->getUserInput();
    $values = NestedArray::getValue($user_input, $pane_form['#parents']);
    if (!empty($values['payment_method'])) {
      $default_option = $values['payment_method'];
      if (substr($default_option, 0, 5) == 'new--') {
        list(, , $payment_gateway_id) = explode('--', $default_option);
        /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $default_payment_gateway */
        $default_payment_gateway = $this->paymentGatewayStorage->load($payment_gateway_id);
        $default_payment_gateway_plugin = $default_payment_gateway->getPlugin();
      }
      else {
        /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $default_payment_method */
        $default_payment_method = $this->paymentMethodStorage->load($default_option);
        $default_payment_gateway_plugin = $default_payment_method->getPaymentGateway()->getPlugin();
      }
    }
    elseif ($order_payment_method) {
      $default_option = $order_payment_method->id();
      $default_payment_gateway_plugin = $order_payment_method->getPaymentGateway()->getPlugin();
    }
    elseif ($order_payment_gateway) {
      $default_payment_gateway_plugin = $order_payment_gateway->getPlugin();
      $default_payment_method_type = $default_payment_gateway_plugin->getDefaultPaymentMethodType();
      $default_option = 'new--' . $default_payment_method_type->getPluginId() . '--' . $order_payment_gateway->id();
    }
    else {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $default_payment_gateway */
      $first_option = end($options);
      $default_payment_gateway = $this->paymentGatewayStorage->load($first_option['payment_gateway']);
      $default_payment_gateway_plugin = $default_payment_gateway->getPlugin();
      $default_option = $first_option['id'];
    }

    // Prepare the form for ajax.
    $pane_form['#wrapper_id'] = Html::getUniqueId('payment-information-wrapper');
    $pane_form['#prefix'] = '<div id="' . $pane_form['#wrapper_id'] . '">';
    $pane_form['#suffix'] = '</div>';

    $pane_form['payment_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment method'),
      '#options' => array_column($options, 'label', 'id'),
      '#default_value' => $default_option,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $pane_form['#wrapper_id'],
      ],
    ];
    foreach ($options as $option_id => $option) {
      $pane_form['payment_method'][$option_id]['#payment_method_plugin'] = $option['payment_method_plugin'];
      $pane_form['payment_method'][$option_id]['#payment_gateway'] = $option['payment_gateway'];
    }

    // Extra elements for new payment methods.
    if (substr($default_option, 0, 5) == 'new--' && !empty($options[$default_option])) {
      if ($default_payment_gateway_plugin instanceof SupportsStoredPaymentMethodsInterface) {
        $payment_method = $this->paymentMethodStorage->create([
          'type' => $options[$default_option]['payment_method_plugin'],
          'payment_gateway' => $options[$default_option]['payment_gateway'],
          'uid' => $this->order->getCustomerId(),
        ]);
        $pane_form['add_payment_method'] = [
          '#type' => 'commerce_payment_gateway_form',
          '#operation' => 'add-payment-method',
          '#default_value' => $payment_method,
        ];
      }
      else {
        $store = $this->order->getStore();
        $billing_profile = $this->order->getBillingProfile();
        if (!$billing_profile) {
          $billing_profile = Profile::create([
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
    }

    return $pane_form;
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

    // Get the payment gateway from pane form.
    $payment_gateway_id = $pane_form['payment_method'][$values['payment_method']]['#payment_gateway'];
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->paymentGatewayStorage->load($payment_gateway_id);

    if ($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface) {
      if (is_numeric($values['payment_method'])) {
        $payment_method = $this->paymentMethodStorage->load($values['payment_method']);
      }
      else {
        $payment_method = $values['add_payment_method'];
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $this->order->payment_gateway = $payment_method->getPaymentGateway();
      $this->order->payment_method = $payment_method;
      $this->order->setBillingProfile($payment_method->getBillingProfile());
    }
    else {
      $this->order->payment_gateway = $payment_gateway;
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
