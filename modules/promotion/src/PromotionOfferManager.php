<?php

namespace Drupal\commerce_promotion;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Provides the Offer plugin manager.
 */
class PromotionOfferManager extends DefaultPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new PromotionOfferManager object.
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
    parent::__construct('Plugin/Commerce/PromotionOffer', $namespaces, $module_handler, 'Drupal\commerce_promotion\Plugin\Commerce\PromotionOffer\PromotionOfferInterface', 'Drupal\commerce_promotion\Annotation\CommercePromotionOffer');

    $this->alterInfo('commerce_promotion_offer_info');
    $this->setCacheBackend($cache_backend, 'commerce_promotion_offer_plugins');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function createInstance($plugin_id, array $configuration = []) {
    $plugin = $this->getFactory()->createInstance($plugin_id, $configuration);

    // If we receive any context values via config set it into the plugin.
    if (!empty($configuration['context'])) {
      foreach ($configuration['context'] as $name => $context) {
        $plugin->setContextValue($name, $context);
      }
    }

    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    foreach (['id', 'label', 'target_entity_type'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new PluginException(sprintf('The promotion offer %s must define the %s property.', $plugin_id, $required_property));
      }
    }

    $target = $definition['target_entity_type'];
    if (!$this->entityTypeManager->getDefinition($target)) {
      throw new PluginException(sprintf('The promotion offer %s must reference a valid entity type, %s given.', $plugin_id, $target));
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
