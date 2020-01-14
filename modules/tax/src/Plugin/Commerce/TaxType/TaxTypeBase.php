<?php

namespace Drupal\commerce_tax\Plugin\Commerce\TaxType;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\commerce_tax\Event\TaxEvents;
use Drupal\commerce_tax\Event\CustomerProfileEvent;
use Drupal\commerce_tax\TaxableType;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   * The parent config entity.
   *
   * Not available while the plugin is being configured.
   *
   * @var \Drupal\commerce_tax\Entity\TaxTypeInterface
   */
  protected $parentEntity;

  /**
   * The ID of the parent config entity.
   *
   * @deprecated in commerce:8.x-2.16 and is removed from commerce:3.x.
   *   Use $this->parentEntity->id() instead.
   *
   * @var string
   */
  protected $entityId;

  /**
   * A cache of prepared customer profiles, keyed by order ID.
   *
   * @var \Drupal\profile\Entity\ProfileInterface
   */
  protected $profiles = [];

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
    if (array_key_exists('_entity', $configuration)) {
      $this->parentEntity = $configuration['_entity'];
      $this->entityId = $this->parentEntity->id();
      unset($configuration['_entity']);
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
  public function __sleep() {
    if (!empty($this->parentEntity)) {
      $this->_parentEntityId = $this->parentEntity->id();
      unset($this->parentEntity);
    }
    unset($this->profiles);

    return parent::__sleep();
  }

  /**
   * {@inheritdoc}
   */
  public function __wakeup() {
    parent::__wakeup();

    if (!empty($this->_parentEntityId)) {
      $tax_type_storage = $this->entityTypeManager->getStorage('commerce_tax_type');
      $this->parentEntity = $tax_type_storage->load($this->_parentEntityId);
      unset($this->_parentEntityId);
    }
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
  public function getWeight() {
    return $this->pluginDefinition['weight'];
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
    $customer_profile = $this->buildCustomerProfile($order);
    // Allow the customer profile to be altered, per order item.
    $event = new CustomerProfileEvent($customer_profile, $order_item);
    $this->eventDispatcher->dispatch(TaxEvents::CUSTOMER_PROFILE, $event);
    $customer_profile = $event->getCustomerProfile();

    return $customer_profile;
  }

  /**
   * Builds a customer profile for the given order.
   *
   * Constructed only for the purposes of tax calculation, never saved.
   * The address comes one of the saved profiles, with the following priority:
   * - Shipping profile
   * - Billing profile
   * - Store profile (if the tax type is display inclusive)
   * The tax number comes from the billing profile, if present.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The customer profile, or NULL if not available yet.
   */
  protected function buildCustomerProfile(OrderInterface $order) {
    $order_id = $order->id();
    if (!isset($this->profiles[$order_id])) {
      $order_profiles = $order->collectProfiles();
      $address = NULL;
      foreach (['shipping', 'billing'] as $scope) {
        if (isset($order_profiles[$scope])) {
          $address_field = $order_profiles[$scope]->get('address');
          if (!$address_field->isEmpty()) {
            $address = $address_field->getValue();
            break;
          }
        }
      }
      if (!$address && $this->isDisplayInclusive()) {
        // Customer is still unknown, but prices are displayed tax-inclusive
        // (VAT scenario), better to show the store's default tax than nothing.
        $address = $order->getStore()->getAddress();
      }
      if (!$address) {
        // A customer profile isn't usable without an address. Stop here.
        return NULL;
      }

      $tax_number = NULL;
      if (isset($order_profiles['billing'])) {
        $tax_number = $order_profiles['billing']->get('tax_number')->getValue();
      }
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $this->profiles[$order_id] = $profile_storage->create([
        'type' => 'customer',
        'uid' => 0,
        'address' => $address,
        'tax_number' => $tax_number,
      ]);
    }

    return $this->profiles[$order_id];
  }

}
