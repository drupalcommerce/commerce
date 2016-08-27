<?php

namespace Drupal\commerce_promotion;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Executable\ExecutableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages discovery and instantiation of promotion condition plugins.
 *
 * @see \Drupal\commerce_promotion\Annotation\CommercePromotionCondition
 * @see plugin_api
 */
class PromotionConditionManager extends DefaultPluginManager implements ExecutableManagerInterface, CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;
  use ContextAwarePluginManagerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PromotionConditionManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type handler.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct('Plugin/Commerce/PromotionCondition', $namespaces, $module_handler, 'Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionInterface', 'Drupal\commerce_promotion\Annotation\CommercePromotionCondition');

    $this->alterInfo('condition_info');
    $this->setCacheBackend($cache_backend, 'condition_plugins');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(ExecutableInterface $condition) {
    /** @var \Drupal\commerce_promotion\Plugin\Commerce\PromotionCondition\PromotionConditionInterface $condition */
    $result = $condition->evaluate();
    return $condition->isNegated() ? !$result : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label', 'target_entity_type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The promotion condition %s must define the %s property.', $plugin_id, $required_property));
      }
    }

    $target = $definition['target_entity_type'];
    if (!$this->entityTypeManager->getDefinition($target)) {
      throw new PluginException(sprintf('The promotion condition %s must reference a valid entity type, %s given.', $plugin_id, $target));
    }

    // If the plugin did not specify a category, use the target entity's label.
    if (empty($definition['category'])) {
      $definition['category'] = $this->entityTypeManager->getDefinition($target)->getLabel();
    }

    // Generate the context definition if it is missing.
    if (empty($definition['context'][$target])) {
      $definition['context'][$target] = new ContextDefinition('entity:' . $target, $definition['category']);
    }
  }

}
