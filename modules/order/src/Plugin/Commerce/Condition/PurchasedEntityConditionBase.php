<?php

namespace Drupal\commerce_order\Plugin\Commerce\Condition;

use Drupal\commerce\EntityUuidMapperInterface;
use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class PurchasedEntityConditionBase extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity UUID mapper.
   *
   * @var \Drupal\commerce\EntityUuidMapperInterface
   */
  protected $entityUuidMapper;

  /**
   * Constructs a new PurchasedEntityConditionBase object.
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
   * @param \Drupal\commerce\EntityUuidMapperInterface $entity_uuid_mapper
   *   The entity UUID mapper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityUuidMapperInterface $entity_uuid_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
      $container->get('commerce.entity_uuid_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'entities' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * Gets the purchasable entity type.
   *
   * @return string
   *   The purchasable entity type.
   */
  protected function getPurchasableEntityType(): string {
    return $this->pluginDefinition['purchasable_entity_type'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $purchasable_entity_type = $this->entityTypeManager->getDefinition($this->getPurchasableEntityType());
    assert($purchasable_entity_type !== NULL);
    $entities = NULL;
    $entity_ids = $this->entityUuidMapper->mapToIds($this->getPurchasableEntityType(), $this->configuration['entities']);
    if (count($entity_ids) > 0) {
      $variation_storage = $this->entityTypeManager->getStorage($purchasable_entity_type->id());
      $entities = $variation_storage->loadMultiple($entity_ids);
    }
    $form['entities'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $purchasable_entity_type->getCollectionLabel(),
      '#default_value' => $entities,
      '#target_type' => $purchasable_entity_type->id(),
      '#tags' => TRUE,
      '#required' => TRUE,
      '#maxlength' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    // Convert selected IDs into UUIDs, and store them.
    $values = $form_state->getValue($form['#parents']);
    $variation_ids = array_column($values['entities'], 'target_id');
    $this->configuration['entities'] = $this->entityUuidMapper->mapFromIds($this->getPurchasableEntityType(), $variation_ids);
  }

  /**
   * Determines whether the given purchasable entity is "valid".
   *
   * @param \Drupal\commerce\PurchasableEntityInterface|null $purchasable_entity
   *   The purchasable entity.
   *
   * @return bool
   *   Whether the given purchasable entity is "valid".
   */
  protected function isValid(PurchasableEntityInterface $purchasable_entity = NULL): bool {
    return $purchasable_entity !== NULL &&
      $purchasable_entity->getEntityTypeId() === $this->getPurchasableEntityType() &&
      in_array($purchasable_entity->uuid(), $this->configuration['entities'], TRUE);
  }

}
