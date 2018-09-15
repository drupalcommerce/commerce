<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the store condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_store",
 *   label = @Translation("Store"),
 *   display_label = @Translation("Store"),
 *   category = @Translation("Order"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderStore extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The store storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storeStorage;

  /**
   * Constructs a new OrderStore object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      // The store UUIDs.
      'stores' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Map the UUIDs back to IDs for the form element.
    $default_value = [];
    foreach ($this->configuration['stores'] as $store_uuid) {
      $stores = $this->storeStorage->loadByProperties(['uuid' => $store_uuid]);
      if ($stores) {
        $store = reset($stores);
        $default_value[] = $store->id();
      }
    }

    $form['stores'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Stores'),
      '#default_value' => $default_value,
      '#target_type' => 'commerce_store',
      '#hide_single_entity' => FALSE,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $stores = $this->storeStorage->loadMultiple($values['stores']);
    // Map the IDs to UUIDs.
    $this->configuration['stores'] = [];
    foreach ($stores as $store) {
      $this->configuration['stores'][] = $store->uuid();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    $this->assertEntity($entity);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity;

    return in_array($order->getStore()->uuid(), $this->configuration['stores']);
  }

}
