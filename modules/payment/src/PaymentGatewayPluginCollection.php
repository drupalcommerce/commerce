<?php

namespace Drupal\commerce_payment;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * A collection of payment gateway plugins.
 */
class PaymentGatewayPluginCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The payment gateway entity ID this plugin collection belongs to.
   *
   * @var string
   */
  protected $entityId;

  /**
   * Constructs a new PaymentGatewayPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string $entity_id
   *   The payment gateway entity ID this plugin collection belongs to.
   */
  public function __construct(PluginManagerInterface $manager, $instance_id, array $configuration, $entity_id) {
    parent::__construct($manager, $instance_id, $configuration);

    $this->entityId = $entity_id;
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface
   *   The payment gateway plugin.
   */
  public function &get($instance_id) {
    return parent::get($instance_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException("The payment gateway '{$this->entityId}' did not specify a plugin.");
    }

    parent::initializePlugin($instance_id);
  }

}
