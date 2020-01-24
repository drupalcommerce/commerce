<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the store condition for orders.
 *
 * @CommerceCondition(
 *   id = "order_store",
 *   label = @Translation("Store", context = "Commerce"),
 *   display_label = @Translation("Store", context = "Commerce"),
 *   category = @Translation("Order", context = "Commerce"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderStore extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

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
   * @param \Drupal\commerce\EntityUuidMapperInterface $entity_uuid_mapper
   *   The entity UUID mapper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityUuidMapperInterface $entity_uuid_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityUuidMapper = $entity_uuid_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('commerce.entity_uuid_mapper')
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
    $store_ids = $this->entityUuidMapper->mapToIds('commerce_store', $this->configuration['stores']);

    $form['stores'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Stores'),
      '#default_value' => $store_ids,
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
    $this->configuration['stores'] = $this->entityUuidMapper->mapFromIds('commerce_store', $values['stores']);
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
