<?php

namespace Drupal\commerce;

use Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of entity trait plugins.
 *
 * @see \Drupal\commerce\Annotation\CommerceEntityTrait
 * @see plugin_api
 */
class EntityTraitManager extends DefaultPluginManager implements EntityTraitManagerInterface {

  /**
   * The configurable field manager.
   *
   * @var \Drupal\commerce\ConfigurableFieldManagerInterface
   */
  protected $configurableFieldManager;

  /**
   * Constructs a new EntityTraitManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\commerce\ConfigurableFieldManagerInterface $configurable_field_manager
   *   The configurable field manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, ConfigurableFieldManagerInterface $configurable_field_manager) {
    parent::__construct('Plugin/Commerce/EntityTrait', $namespaces, $module_handler, 'Drupal\commerce\Plugin\Commerce\EntityTrait\EntityTraitInterface', 'Drupal\commerce\Annotation\CommerceEntityTrait');

    $this->alterInfo('commerce_entity_trait_info');
    $this->setCacheBackend($cache_backend, 'commerce_entity_trait_plugins');
    $this->configurableFieldManager = $configurable_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The entity trait %s must define the %s property.', $plugin_id, $required_property));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByEntityType($entity_type_id) {
    $definitions = $this->getDefinitions();
    $definitions = array_filter($definitions, function ($definition) use ($entity_type_id) {
      return empty($definition['entity_types']) || in_array($entity_type_id, $definition['entity_types']);
    });

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function detectConflicts(EntityTraitInterface $trait, array $installed_traits) {
    $field_names = array_keys($trait->buildFieldDefinitions());
    if (!$field_names) {
      return [];
    }

    $conflicting_traits = [];
    foreach ($installed_traits as $installed_trait) {
      $installed_field_names = array_keys($installed_trait->buildFieldDefinitions());
      if (array_intersect($field_names, $installed_field_names)) {
        $conflicting_traits[] = $installed_trait;
      }
    }

    return $conflicting_traits;
  }

  /**
   * {@inheritdoc}
   */
  public function installTrait(EntityTraitInterface $trait, $entity_type_id, $bundle) {
    // The fields provided by an entity trait are maintained as locked
    // configurable fields, for simplicity.
    foreach ($trait->buildFieldDefinitions() as $field_name => $field_definition) {
      $field_definition->setTargetEntityTypeId($entity_type_id);
      $field_definition->setTargetBundle($bundle);
      $field_definition->setName($field_name);

      $this->configurableFieldManager->createField($field_definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function canUninstallTrait(EntityTraitInterface $trait, $entity_type_id, $bundle) {
    foreach ($trait->buildFieldDefinitions() as $field_name => $field_definition) {
      $field_definition->setTargetEntityTypeId($entity_type_id);
      $field_definition->setTargetBundle($bundle);
      $field_definition->setName($field_name);

      if ($this->configurableFieldManager->hasData($field_definition)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function uninstallTrait(EntityTraitInterface $trait, $entity_type_id, $bundle) {
    foreach ($trait->buildFieldDefinitions() as $field_name => $field_definition) {
      $field_definition->setTargetEntityTypeId($entity_type_id);
      $field_definition->setTargetBundle($bundle);
      $field_definition->setName($field_name);

      $this->configurableFieldManager->deleteField($field_definition);
    }
  }

}
