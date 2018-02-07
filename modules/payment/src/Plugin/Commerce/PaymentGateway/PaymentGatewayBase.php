<?php

namespace Drupal\commerce_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_price\Calculator;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the base class for payment gateways.
 */
abstract class PaymentGatewayBase extends PluginBase implements PaymentGatewayInterface, ContainerFactoryPluginInterface {

  use PluginWithFormsTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The ID of the parent config entity.
   *
   * Not available while the plugin is being configured.
   *
   * @var string
   */
  protected $entityId;

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
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

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
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    if (array_key_exists('_entity_id', $configuration)) {
      $this->entityId = $configuration['_entity_id'];
      unset($configuration['_entity_id']);
    }
    // Instantiate the types right away to ensure that their IDs are valid.
    $this->paymentType = $payment_type_manager->createInstance($this->pluginDefinition['payment_type']);
    foreach ($this->pluginDefinition['payment_method_types'] as $plugin_id) {
      $this->paymentMethodTypes[$plugin_id] = $payment_method_type_manager->createInstance($plugin_id);
    }
    $this->pluginDefinition['forms'] += $this->getDefaultForms();
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
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time')
    );
  }

  /**
   * Gets the default payment gateway forms.
   *
   * @return array
   *   A list of plugin form classes keyed by operation.
   */
  protected function getDefaultForms() {
    $default_forms = [];
    if ($this instanceof SupportsStoredPaymentMethodsInterface) {
      $default_forms['add-payment-method'] = 'Drupal\commerce_payment\PluginForm\PaymentMethodAddForm';
    }
    if ($this instanceof SupportsUpdatingStoredPaymentMethodsInterface) {
      $default_forms['edit-payment-method'] = 'Drupal\commerce_payment\PluginForm\PaymentMethodEditForm';
    }
    if ($this instanceof SupportsAuthorizationsInterface) {
      $default_forms['capture-payment'] = 'Drupal\commerce_payment\PluginForm\PaymentCaptureForm';
    }
    if ($this instanceof SupportsVoidsInterface) {
      $default_forms['void-payment'] = 'Drupal\commerce_payment\PluginForm\PaymentVoidForm';
    }
    if ($this instanceof SupportsRefundsInterface) {
      $default_forms['refund-payment'] = 'Drupal\commerce_payment\PluginForm\PaymentRefundForm';
    }

    return $default_forms;
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
  public function getJsLibrary() {
    $js_library = NULL;
    if (!empty($this->pluginDefinition['js_library'])) {
      $js_library = $this->pluginDefinition['js_library'];
    }
    return $js_library;
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
  public function getDefaultPaymentMethodType() {
    $default_payment_method_type = $this->pluginDefinition['default_payment_method_type'];
    if (!isset($this->paymentMethodTypes[$default_payment_method_type])) {
      throw new \InvalidArgumentException('Invalid default_payment_method_type specified.');
    }
    return $this->paymentMethodTypes[$default_payment_method_type];
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
    // Providing a default for payment_metod_types in defaultConfiguration()
    // doesn't work because NestedArray::mergeDeep causes duplicates.
    if (empty($this->configuration['payment_method_types'])) {
      $this->configuration['payment_method_types'][] = 'credit_card';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $modes = array_keys($this->getSupportedModes());

    return [
      'display_label' => $this->pluginDefinition['display_label'],
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

    $form['display_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display name'),
      '#description' => t('Shown to customers during checkout.'),
      '#default_value' => $this->configuration['display_label'],
    ];

    if (count($modes) > 1) {
      $form['mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Mode'),
        '#options' => $modes,
        '#default_value' => $this->configuration['mode'],
        '#required' => TRUE,
      ];
    }
    else {
      $mode_names = array_keys($modes);
      $form['mode'] = [
        '#type' => 'value',
        '#value' => reset($mode_names),
      ];
    }

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

      $this->configuration = [];
      $this->configuration['display_label'] = $values['display_label'];
      $this->configuration['mode'] = $values['mode'];
      $this->configuration['payment_method_types'] = array_keys($values['payment_method_types']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->value;
    $operations = [];
    if ($this instanceof SupportsAuthorizationsInterface) {
      $operations['capture'] = [
        'title' => $this->t('Capture'),
        'page_title' => $this->t('Capture payment'),
        'plugin_form' => 'capture-payment',
        'access' => $payment_state == 'authorization',
      ];
    }
    if ($this instanceof SupportsVoidsInterface) {
      $operations['void'] = [
        'title' => $this->t('Void'),
        'page_title' => $this->t('Void payment'),
        'plugin_form' => 'void-payment',
        'access' => $payment_state == 'authorization',
      ];
    }
    if ($this instanceof SupportsRefundsInterface) {
      $operations['refund'] = [
        'title' => $this->t('Refund'),
        'page_title' => $this->t('Refund payment'),
        'plugin_form' => 'refund-payment',
        'access' => in_array($payment_state, ['completed', 'partially_refunded']),
      ];
    }

    return $operations;
  }

  /**
   * Converts the given amount to its minor units.
   *
   * For example, 9.99 USD becomes 999.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount.
   *
   * @return int
   *   The amount in minor units, as an integer.
   */
  protected function toMinorUnits(Price $amount) {
    $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency */
    $currency = $currency_storage->load($amount->getCurrencyCode());
    $fraction_digits = $currency->getFractionDigits();
    $number = $amount->getNumber();
    if ($fraction_digits > 0) {
      $number = Calculator::multiply($number, pow(10, $fraction_digits));
    }

    return round($number, 0);
  }

  /**
   * Gets the remote customer ID for the given user.
   *
   * The remote customer ID is specific to a payment gateway instance
   * in the configured mode. This allows the gateway to skip test customers
   * after the gateway has been switched to live mode.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   *
   * @return string
   *   The remote customer ID, or NULL if none found.
   */
  protected function getRemoteCustomerId(UserInterface $account) {
    $remote_id = NULL;
    if ($account->isAuthenticated()) {
      /** @var \Drupal\commerce\Plugin\Field\FieldType\RemoteIdFieldItemListInterface $remote_ids */
      $remote_ids = $account->get('commerce_remote_id');
      $remote_id = $remote_ids->getByProvider($this->entityId . '|' . $this->getMode());
      // Gateways used to key customer IDs by module name, migrate that data.
      if (!$remote_id) {
        $remote_id = $remote_ids->getByProvider($this->pluginDefinition['provider']);
        if ($remote_id) {
          $remote_ids->setByProvider($this->pluginDefinition['provider'], NULL);
          $remote_ids->setByProvider($this->entityId . '|' . $this->getMode(), $remote_id);
          $account->save();
        }
      }
    }

    return $remote_id;
  }

  /**
   * Sets the remote customer ID for the given user.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param string $remote_id
   *   The remote customer ID.
   */
  protected function setRemoteCustomerId(UserInterface $account, $remote_id) {
    if ($account->isAuthenticated()) {
      /** @var \Drupal\commerce\Plugin\Field\FieldType\RemoteIdFieldItemListInterface $remote_ids */
      $remote_ids = $account->get('commerce_remote_id');
      $remote_ids->setByProvider($this->entityId . '|' . $this->getMode(), $remote_id);
    }
  }

  /**
   * Asserts that the payment state matches one of the allowed states.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param string[] $states
   *   The allowed states.
   *
   * @throws \InvalidArgumentException
   *   Thrown if the payment state does not match the allowed states.
   */
  protected function assertPaymentState(PaymentInterface $payment, array $states) {
    $state = $payment->getState()->value;
    if (!in_array($state, $states)) {
      throw new \InvalidArgumentException(sprintf('The provided payment is in an invalid state ("%s").', $state));
    }
  }

  /**
   * Asserts that the payment method is neither empty nor expired.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the payment method is empty.
   * @throws \Drupal\commerce_payment\Exception\HardDeclineException
   *   Thrown when the payment method has expired.
   */
  protected function assertPaymentMethod(PaymentMethodInterface $payment_method = NULL) {
    if (empty($payment_method)) {
      throw new \InvalidArgumentException('The provided payment has no payment method referenced.');
    }
    if ($payment_method->isExpired()) {
      throw new HardDeclineException('The provided payment method has expired');
    }
  }

  /**
   * Asserts that the refund amount is valid.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param \Drupal\commerce_price\Price $refund_amount
   *   The allowed states.
   *
   * @throws \Drupal\commerce_payment\Exception\InvalidRequestException
   *   Thrown when the refund amount is larger than the payment balance.
   */
  protected function assertRefundAmount(PaymentInterface $payment, Price $refund_amount) {
    $balance = $payment->getBalance();
    if ($refund_amount->greaterThan($balance)) {
      throw new InvalidRequestException(sprintf("Can't refund more than %s.", $balance->__toString()));
    }
  }

}
