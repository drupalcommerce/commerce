<?php

namespace Drupal\commerce_payment;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class PaymentOptionsBuilder implements PaymentOptionsBuilderInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PaymentOptionsBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptions(OrderInterface $order, array $payment_gateways = []) {
    if (empty($payment_gateways)) {
      /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
      $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
      $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($order);
    }
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $payment_gateways_with_payment_methods */
    $payment_gateways_with_payment_methods = array_filter($payment_gateways, function ($payment_gateway) {
      /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
      return $payment_gateway->getPlugin() instanceof SupportsStoredPaymentMethodsInterface;
    });

    $options = [];
    // 1) Add options to reuse stored payment methods for known customers.
    $customer = $order->getCustomer();
    if ($customer->isAuthenticated()) {
      $billing_countries = $order->getStore()->getBillingCountries();
      /** @var \Drupal\commerce_payment\PaymentMethodStorageInterface $payment_method_storage */
      $payment_method_storage = $this->entityTypeManager->getStorage('commerce_payment_method');

      foreach ($payment_gateways_with_payment_methods as $payment_gateway) {
        $payment_methods = $payment_method_storage->loadReusable($customer, $payment_gateway, $billing_countries);

        foreach ($payment_methods as $payment_method_id => $payment_method) {
          $options[$payment_method_id] = new PaymentOption([
            'id' => $payment_method_id,
            'label' => $payment_method->label(),
            'payment_gateway_id' => $payment_gateway->id(),
            'payment_method_id' => $payment_method_id,
          ]);
        }
      }
    }

    // 2) Add the order's payment method if it was not included above.
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $order_payment_method */
    $order_payment_method = $order->get('payment_method')->entity;
    if ($order_payment_method) {
      $order_payment_method_id = $order_payment_method->id();
      // Make sure that the payment method's gateway is still available.
      $payment_gateway_id = $order_payment_method->getPaymentGatewayId();
      $payment_gateway_ids = EntityHelper::extractIds($payment_gateways_with_payment_methods);

      if (in_array($payment_gateway_id, $payment_gateway_ids) && !isset($options[$order_payment_method_id])) {
        $options[$order_payment_method_id] = new PaymentOption([
          'id' => $order_payment_method_id,
          'label' => $order_payment_method->label(),
          'payment_gateway_id' => $order_payment_method->getPaymentGatewayId(),
          'payment_method_id' => $order_payment_method_id,
        ]);
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

        $options[$option_id] = new PaymentOption([
          'id' => $option_id,
          'label' => $option_label,
          'payment_gateway_id' => $payment_gateway->id(),
          'payment_method_type_id' => $payment_method_type_id,
        ]);
      }
    }

    // 4) Add options for the remaining gateways.
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface[] $other_payment_gateways */
    $other_payment_gateways = array_diff_key($payment_gateways, $payment_gateways_with_payment_methods);
    foreach ($other_payment_gateways as $payment_gateway) {
      $payment_gateway_id = $payment_gateway->id();
      $options[$payment_gateway_id] = new PaymentOption([
        'id' => $payment_gateway_id,
        'label' => $payment_gateway->getPlugin()->getDisplayLabel(),
        'payment_gateway_id' => $payment_gateway_id,
      ]);
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function selectDefaultOption(OrderInterface $order, array $options) {
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $order_payment_gateway */
    $order_payment_gateway = $order->get('payment_gateway')->entity;
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $order_payment_method */
    $order_payment_method = $order->get('payment_method')->entity;

    $default_option_id = NULL;
    if ($order_payment_method) {
      $default_option_id = $order_payment_method->id();
    }
    elseif ($order_payment_gateway && !($order_payment_gateway instanceof SupportsStoredPaymentMethodsInterface)) {
      $default_option_id = $order_payment_gateway->id();
    }
    // The order doesn't have a payment method/gateway specified, or it has, but it is no longer available.
    if (!$default_option_id || !isset($options[$default_option_id])) {
      $option_ids = array_keys($options);
      $default_option_id = reset($option_ids);
    }

    return $options[$default_option_id];
  }

}
