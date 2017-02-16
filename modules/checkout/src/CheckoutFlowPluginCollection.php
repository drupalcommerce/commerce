<?php

namespace Drupal\commerce_checkout;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * A collection of checkout flow plugins.
 */
class CheckoutFlowPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The checkout flow entity ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $entityId;

  /**
   * Constructs a new CheckoutFlowPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $entity_id
   *   The checkout flow entity ID this plugin collection belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $entity_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->entityId = $entity_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface
   *   The checkout flow plugin.
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The checkout flow '{$this->entityId}' did not specify a plugin.");
    }

    parent::initializePlugin($instance_id);
  }

}
