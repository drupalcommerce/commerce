<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\commerce_tax\Event\TaxEvents;
use Drupal\commerce_tax\Event\CustomerProfileEvent;
use Drupal\commerce_tax\TaxableType;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the base class for tax types.
 */
abstract class TaxTypeBase extends PluginBase implements TaxTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The ID of the parent config entity.
   *
   * Not available while the plugin is being configured.
   *
   * @var string
   */
  protected $entityId;

  /**
   * A cache of instantiated store profiles.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $storeProfiles = [];

  /**
   * Constructs a new TaxTypeBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
    if (array_key_exists('_entity_id', $configuration)) {
      $this->entityId = $configuration['_entity_id'];
      unset($configuration['_entity_id']);
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
      $container->get('event_dispatcher')
    );
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
  public function defaultConfiguration() {
    return [
      'display_inclusive' => TRUE,
    ];
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['display_inclusive'] = [
      '#type' => 'checkbox',
      '#title' => t('Display taxes of this type inclusive in product prices.'),
      '#default_value' => $this->configuration['display_inclusive'],
    ];

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
      $this->configuration = [];
      $this->configuration['display_inclusive'] = $values['display_inclusive'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function isDisplayInclusive() {
    return !empty($this->configuration['display_inclusive']);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(OrderInterface $order) {
    return TRUE;
  }

  /**
   * Gets the calculation date for the given order.
   *
   * Uses the placed timestamp, if the order has been placed.
   * Falls back to the current date otherwise.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\Core\Datetime\DrupalDateTime
   *   The calculation date.
   */
  protected function getCalculationDate(OrderInterface $order) {
    if ($timestamp = $order->getPlacedTime()) {
      $date = DrupalDateTime::createFromTimestamp($timestamp);
    }
    else {
      $date = new DrupalDateTime();
    }
    return $date;
  }

  /**
   * Gets the taxable type for the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return string
   *   The taxable type, a \Drupal\commerce_tax\TaxableType constant.
   */
  protected function getTaxableType(OrderItemInterface $order_item) {
    // @todo Allow the taxable type to be specified on the product type too.
    $order_item_type_storage = $this->entityTypeManager->getStorage('commerce_order_item_type');
    $order_item_type = $order_item_type_storage->load($order_item->bundle());
    $taxable_type = $order_item_type->getThirdPartySetting('commerce_tax', 'taxable_type', TaxableType::getDefault());

    return $taxable_type;
  }

  /**
   * Resolves the customer profile for the given order item.
   *
   * @param \Drupal\commerce_order\Entity\OrderItemInterface $order_item
   *   The order item.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The customer profile, or NULL if not yet known.
   */
  protected function resolveCustomerProfile(OrderItemInterface $order_item) {
    $order = $order_item->getOrder();
    $store = $order->getStore();
    $prices_include_tax = $store->get('prices_include_tax')->value;
    $customer_profile = $order->getBillingProfile();
    // A shipping profile is prefered, when available.
    $event = new CustomerProfileEvent($customer_profile, $order_item);
    $this->eventDispatcher->dispatch(TaxEvents::CUSTOMER_PROFILE, $event);
    $customer_profile = $event->getCustomerProfile();
    if (!$customer_profile && $prices_include_tax) {
      // The customer is still unknown, but prices include tax (VAT scenario),
      // better to show the store's default tax than nothing.
      $customer_profile = $this->buildStoreProfile($store);
    }

    return $customer_profile;
  }

  /**
   * Builds a customer profile for the given store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store.
   *
   * @return \Drupal\profile\Entity\ProfileInterface
   *   The customer profile.
   */
  protected function buildStoreProfile(StoreInterface $store) {
    $store_id = $store->id();
    if (!isset($this->storeProfiles[$store_id])) {
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $this->storeProfiles[$store_id] = $profile_storage->create([
        'type' => 'customer',
        'uid' => $store->getOwnerId(),
        'address' => $store->getAddress(),
      ]);
    }

    return $this->storeProfiles[$store_id];
  }

}
