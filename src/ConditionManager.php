<?php

namespace Drupal\commerce;

use Drupal\commerce\Event\CommerceEvents;
use Drupal\commerce\Event\FilterConditionsEvent;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

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
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct('Plugin/Commerce/Condition', $namespaces, $module_handler, 'Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface', 'Drupal\commerce\Annotation\CommerceCondition');

    $this->alterInfo('commerce_condition_info');
    $this->setCacheBackend($cache_backend, 'commerce_condition_plugins');
    $this->entityTypeManager = $entity_type_manager;
    $this->eventDispatcher = $event_dispatcher;
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
  public function getFilteredDefinitions($parent_entity_type_id, array $entity_type_ids) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $plugin_id => $definition) {
      // Filter by entity type.
      if (!in_array($definition['entity_type'], $entity_type_ids)) {
        unset($definitions[$plugin_id]);
        continue;
      }
      // Filter by parent_entity_type, if specified by the plugin.
      if (!empty($definition['parent_entity_type'])) {
        if ($definition['parent_entity_type'] != $parent_entity_type_id) {
          unset($definitions[$plugin_id]);
        }
      }
    }
    // Allow modules to filter the condition list.
    $event = new FilterConditionsEvent($definitions, $parent_entity_type_id);
    $this->eventDispatcher->dispatch(CommerceEvents::FILTER_CONDITIONS, $event);
    $definitions = $event->getDefinitions();
    // Sort by weigh and display label.
    uasort($definitions, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
        return strnatcasecmp($a['display_label'], $b['display_label']);
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });

    return $definitions;
  }

}
