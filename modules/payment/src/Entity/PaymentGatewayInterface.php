<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;

/**
 * Defines the interface for payment gateway configuration entities.
 *
 * Stores configuration for payment gateway plugins.
 */
interface PaymentGatewayInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Gets the payment gateway weight.
   *
   * @return string
   *   The payment gateway weight.
   */
  public function getWeight();

  /**
   * Sets the payment gateway weight.
   *
   * @param int $weight
   *   The payment gateway weight.
   *
   * @return $this
   */
  public function setWeight($weight);

  /**
   * Gets the payment gateway plugin.
   *
   * @return \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface
   *   The payment gateway plugin.
   */
  public function getPlugin();

  /**
   * Gets the payment gateway plugin ID.
   *
   * @return string
   *   The payment gateway plugin ID.
   */
  public function getPluginId();

  /**
   * Sets the payment gateway plugin ID.
   *
   * @param string $plugin_id
   *   The payment gateway plugin ID.
   *
   * @return $this
   */
  public function setPluginId($plugin_id);

  /**
   * Gets the payment gateway plugin configuration.
   *
   * @return array
   *   The payment gateway plugin configuration.
   */
  public function getPluginConfiguration();

  /**
   * Sets the payment gateway plugin configuration.
   *
   * @param array $configuration
   *   The payment gateway plugin configuration.
   *
   * @return $this
   */
  public function setPluginConfiguration(array $configuration);

  /**
   * Gets the payment gateway conditions.
   *
   * @return \Drupal\commerce\Plugin\Commerce\Condition\ConditionInterface[]
   *   The payment gateway conditions.
   */
  public function getConditions();

  /**
   * Gets the payment gateway condition operator.
   *
   * @return string
   *   The condition operator. Possible values: AND, OR.
   */
  public function getConditionOperator();

  /**
   * Sets the payment gateway condition operator.
   *
   * @param string $condition_operator
   *   The condition operator.
   *
   * @return $this
   */
  public function setConditionOperator($condition_operator);

  /**
   * Checks whether the payment gateway applies to the given order.
   *
   * Ensures that the conditions pass.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if payment gateway applies, FALSE otherwise.
   */
  public function applies(OrderInterface $order);

}
