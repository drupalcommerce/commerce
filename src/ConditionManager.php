<?php

namespace Drupal\commerce;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of condition plugins.
 *
 * @see \Drupal\commerce\Annotation\CommerceCondition
 * @see plugin_api
 */
class ConditionManager extends DefaultPluginManager implements ConditionManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ConditionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/Commerce/Condition', $namespaces, $module_handler, 'Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface', 'Drupal\commerce\Annotation\CommerceCondition');

    $this->alterInfo('commerce_condition_info');
    $this->setCacheBackend($cache_backend, 'commerce_condition_plugins');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label', 'entity_type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The condition "%s" must define the "%s" property.', $plugin_id, $required_property));
      }
    }

    $entity_type_id = $definition['entity_type'];
    if (!$this->entityTypeManager->getDefinition($entity_type_id)) {
      throw new PluginException(sprintf('The condition "%s" must specify a valid entity type, "%s" given.', $plugin_id, $entity_type_id));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByEntityTypes(array $entity_types) {
    $definitions = $this->getDefinitions();
    if (!empty($entity_types)) {
      // Remove conditions not matching the specified entity types.
      $definitions = array_filter($definitions, function ($definition) use ($entity_types) {
        return in_array($definition['entity_type'], $entity_types);
      });
    }

    return $definitions;
  }

}
