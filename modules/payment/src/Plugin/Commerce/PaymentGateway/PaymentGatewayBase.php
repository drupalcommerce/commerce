<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for payment gateways.
 */
abstract class PaymentGatewayBase extends PluginBase implements PaymentGatewayInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The payment type used by the gateway.
   *
   * @var \Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentTypeInterface
   */
  protected $paymentType;

  /**
   * The payment method types handled by the gateway.
   *
   * @var \Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeInterface[]
   */
  protected $paymentMethodTypes;

  /**
   * Constructs a new PaymentGatewayBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_payment\PaymentTypeManager $payment_type_manager
   *   The payment type manager.
   * @param \Drupal\commerce_payment\PaymentMethodTypeManager $payment_method_type_manager
   *   The payment method type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    // Instantiate the types right away to ensure that their IDs are valid.
    $this->paymentType = $payment_type_manager->createInstance($this->pluginDefinition['payment_type']);
    foreach ($this->pluginDefinition['payment_method_types'] as $plugin_id) {
      $this->paymentMethodTypes[$plugin_id] = $payment_method_type_manager->createInstance($plugin_id);
    }
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->configuration['display_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMode() {
    return $this->configuration['mode'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSupportedModes() {
    return $this->pluginDefinition['modes'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentType() {
    return $this->paymentType;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentMethodTypes() {
    // Filter out payment method types disabled by the merchant.
    return array_intersect_key($this->paymentMethodTypes, array_flip($this->configuration['payment_method_types']));
  }

  /**
   * {@inheritdoc}
   */
  public function getCreditCardTypes() {
    // @todo Allow the list to be restricted by the merchant.
    return array_intersect_key(CreditCard::getTypes(), array_flip($this->pluginDefinition['credit_card_types']));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $modes = array_keys($this->getSupportedModes());

    return [
      'mode' => $modes ? reset($modes) : '',
      'payment_method_types' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $modes = $this->getSupportedModes();
    $payment_method_types = array_map(function ($payment_method_type) {
      return $payment_method_type->getLabel();
    }, $this->paymentMethodTypes);

    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#options' => $modes,
      '#default_value' => $this->configuration['mode'],
      '#required' => TRUE,
      '#access' => !empty($modes),
    ];
    if (count($payment_method_types) > 1) {
      $form['payment_method_types'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Payment method types'),
        '#options' => $payment_method_types,
        '#default_value' => $this->configuration['payment_method_types'],
        '#required' => TRUE,
      ];
    }
    else {
      $form['payment_method_types'] = [
        '#type' => 'value',
        '#value' => $payment_method_types,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $values['payment_method_types'] = array_filter($values['payment_method_types']);

      $this->configuration['mode'] = $values['mode'];
      $this->configuration['payment_method_types'] = array_keys($values['payment_method_types']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation) {
    $forms = $this->pluginDefinition['forms'];
    return isset($forms[$operation]) ? $forms[$operation] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function hasFormClass($operation) {
    return isset($this->pluginDefinition['forms'][$operation]);
  }

}
