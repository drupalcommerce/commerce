<?php

namespace Drupal\commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the payment instructions pane.
 *
 * @CommerceCheckoutPane(
 *   id = "payment_instructions",
 *   label = @Translation("Payment instructions"),
 *   default_step = "complete",
 *   wrapper_element = "fieldset",
 * )
 */
class PaymentInstructions extends CheckoutPaneBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PaymentInstructions object.
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
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // The payment gateway is currently always required to be set.
    if ($this->order->get('payment_gateway')->isEmpty()) {
      return [];
    }

    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $this->order->payment_gateway->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment_gateway->getPlugin();

    if ($payment_gateway_plugin instanceof ManualPaymentGatewayInterface && $payment_gateway_plugin->getPaymentInstructions()) {
      $pane_form += $payment_gateway_plugin->getPaymentInstructions();
      return $pane_form;
    }

    return [];
  }

}
