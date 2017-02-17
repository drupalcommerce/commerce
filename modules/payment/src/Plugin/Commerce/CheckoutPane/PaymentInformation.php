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
use Drupal\Core\Render\RendererInterface;
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
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The payment gateway storage.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $paymentGatewayStorage
   */
  protected $paymentGatewayStorage;

  /**
   * The payment method storage.
   *
   * @var \Drupal\commerce_payment\PaymentMethodStorageInterface $paymentMethodStorage
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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
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
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = '';
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    if (!$payment_gateway) {
      return $summary;
    }

    $payment_gateway_plugin = $payment_gateway->getPlugin();
    $payment_method = $this->order->payment_method->entity;
    if ($payment_gateway_plugin instanceof SupportsStoredPaymentMethodsInterface && $payment_method) {
      $view_builder = $this->entityTypeManager->getViewBuilder('commerce_payment_method');
      $payment_method_view = $view_builder->view($payment_method, 'default');
      $summary = $this->renderer->render($payment_method_view);
    }
    else {
      $billing_profile = $this->order->getBillingProfile();
      if ($billing_profile) {
        $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
        $profile_view = $profile_view_builder->view($billing_profile, 'default');
        $summary = $payment_gateway->getPlugin()->getDisplayLabel();
        $summary .= $this->renderer->render($profile_view);
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

    // Get the default payment gateway.
    $values = $form_state->getValue($pane_form['#parents']);
    if (!empty($values['payment_gateway'])) {
      $default_value = $values['payment_gateway'];
      $default_payment_gateway = $this->paymentGatewayStorage->load($default_value);
    }
    elseif (!empty($this->order->payment_gateway->entity)) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $default_payment_gateway */
      $default_payment_gateway = $this->order->payment_gateway->entity;
      $default_value = $default_payment_gateway->id();
    }
    else {
      $default_payment_gateway = reset($payment_gateways);
      $default_value = $default_payment_gateway->id();
    }
    $default_payment_gateway_plugin = $default_payment_gateway->getPlugin();

    $options = [];
    foreach ($payment_gateways as $payment_gateway) {
      $payment_gateway_plugin = $payment_gateway->getPlugin();
      $options[$payment_gateway->id()] = $payment_gateway_plugin->getLabel();
    }

    $pane_form['#wrapper_id'] = Html::getUniqueId('payment-information-wrapper');
    $pane_form['#prefix'] = '<div id="' . $pane_form['#wrapper_id'] . '">';
    $pane_form['#suffix'] = '</div>';
    $pane_form['payment_gateway'] = [
      '#type' => 'radios',
      '#title' => $this->t('Payment gateway'),
      '#options' => $options,
      '#default_value' => $default_value,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $pane_form['#wrapper_id'],
      ],
      '#required' => TRUE,
    ];

    if ($default_payment_gateway_plugin instanceof SupportsStoredPaymentMethodsInterface) {
      $this->attachPaymentMethodForm($default_payment_gateway, $pane_form, $form_state);
    }
    else {
      $this->attachBillingInformationForm($pane_form, $form_state);
    }

    return $pane_form;
  }

  /**
   * Creates the billing information form.
   *
   * @param array $pane_form
   *   The pane form, containing the following basic properties:
   *   - #parents: Identifies the position of the pane form in the overall
   *     parent form, and identifies the location where the field values are
   *     placed within $form_state->getValues().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   */
  protected function attachBillingInformationForm(array &$pane_form, FormStateInterface $form_state) {
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

  /**
   * Creates the payment method selection form for supported selected gateway.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway
   *   The payment gateways available.
   * @param array $pane_form
   *   The pane form, containing the following basic properties:
   *   - #parents: Identifies the position of the pane form in the overall
   *     parent form, and identifies the location where the field values are
   *     placed within $form_state->getValues().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   */
  protected function attachPaymentMethodForm($payment_gateway, array &$pane_form, FormStateInterface $form_state) {
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    // Prepare the payment method form for ajax.
    $wrapper_id = Html::getUniqueId('payment-method-form-wrapper');
    $pane_form['payment_method_form'] = [
      '#wrapper_id' => $wrapper_id,
      '#type' => 'container',
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $options = [];
    $default_option = NULL;
    $customer = $this->order->getCustomer();
    if ($customer) {
      $payment_methods = $this->paymentMethodStorage->loadReusable($customer, $payment_gateway);
      foreach ($payment_methods as $payment_method) {
        $options[$payment_method->id()] = $payment_method->label();
      }
    }
    $payment_method_types = $payment_gateway_plugin->getPaymentMethodTypes();
    foreach ($payment_method_types as $payment_method_type) {
      $id = 'new_' . $payment_method_type->getPluginId();
      $options[$id] = $payment_method_type->getCreateLabel();
    }
    $values = $form_state->getValue($pane_form['#parents']);

    // Get the default payment method.
    if (!empty($values['payment_method_form']['payment_method']) && !empty($options[$values['payment_method_form']['payment_method']])) {
      $default_value = $values['payment_method_form']['payment_method'];
    }
    elseif (!empty($this->order->payment_method->entity)) {
      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $default_payment_method */
      $default_payment_method = $this->order->payment_method->entity;
      $default_value = $default_payment_method->id();
    }
    else {
      $default_payment_method_type = $payment_gateway_plugin->getDefaultPaymentMethodType();
      $default_value = 'new_' . $default_payment_method_type->getPluginId();
    }

    $parents = array_merge($pane_form['#parents'], ['payment_method_form', 'payment_method']);
    $pane_form['payment_method_form']['payment_method'] = [
      '#parents' => $parents,
      '#array_parents' => $parents,
      '#type' => 'radios',
      '#title' => $this->t('Payment method'),
      '#options' => $options,
      '#default_value' => $default_value,
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => $pane_form['payment_method_form']['#wrapper_id'],
      ],
      '#required' => TRUE,
    ];
    if (substr($default_value, 0, 4) == 'new_') {
      $payment_method = $this->paymentMethodStorage->create([
        'type' => substr($default_value, 4),
        'payment_gateway' => $payment_gateway->id(),
        'uid' => $this->order->getCustomerId(),
      ]);
      $parents = array_merge($pane_form['#parents'], ['payment_method_form', 'add_payment_method']);
      $pane_form['payment_method_form']['add_payment_method'] = [
        '#parents' => $parents,
        '#array_parents' => $parents,
        '#type' => 'commerce_payment_gateway_form',
        '#operation' => 'add-payment-method',
        '#default_value' => $payment_method,
      ];
    }
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $parents = $form_state->getTriggeringElement()['#parents'];
    array_pop($parents);
    $form_state->setRebuild();
    $element = NestedArray::getValue($form, $parents);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->paymentGatewayStorage->load($values['payment_gateway']);

    if ($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface) {
      if (!isset($values['payment_method_form']['payment_method'])) {
        $form_state->setError($complete_form, $this->noPaymentGatewayErrorMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->paymentGatewayStorage->load($values['payment_gateway']);

    if ($payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface) {
      if (is_numeric($values['payment_method_form']['payment_method'])) {
        $payment_method = $this->paymentMethodStorage->load($values['payment_method_form']['payment_method']);
      }
      else {
        $payment_method = $values['payment_method_form']['add_payment_method'];
      }

      /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
      $this->order->payment_gateway = $payment_method->getPaymentGateway();
      $this->order->payment_method = $payment_method;
      $this->order->setBillingProfile($payment_method->getBillingProfile());
    }
    else {
      $this->order->payment_gateway = $payment_gateway;
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
