<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\commerce_payment\PaymentGatewayPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the payment gateway entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_payment_gateway",
 *   label = @Translation("Payment gateway"),
 *   label_singular = @Translation("payment gateway"),
 *   label_plural = @Translation("payment gateways"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment gateway",
 *     plural = "@count payment gateways",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_payment\PaymentGatewayListBuilder",
 *     "storage" = "Drupal\commerce_payment\PaymentGatewayStorage",
 *     "form" = {
 *       "add" = "Drupal\commerce_payment\Form\PaymentGatewayForm",
 *       "edit" = "Drupal\commerce_payment\Form\PaymentGatewayForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer commerce_payment_gateway",
 *   config_prefix = "commerce_payment_gateway",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *     "status" = "status",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
 *     "status",
 *     "plugin",
 *     "configuration",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/payment-gateways/add",
 *     "edit-form" = "/admin/commerce/config/payment-gateways/manage/{commerce_payment_gateway}",
 *     "delete-form" = "/admin/commerce/config/payment-gateways/manage/{commerce_payment_gateway}/delete",
 *     "collection" =  "/admin/commerce/config/payment-gateways"
 *   }
 * )
 */
class PaymentGateway extends ConfigEntityBase implements PaymentGatewayInterface {

  /**
   * The payment gateway ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The payment gateway label.
   *
   * @var string
   */
  protected $label;

  /**
   * The payment gateway weight.
   *
   * @var int
   */
  protected $weight;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin;

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin collection that holds the payment gateway plugin.
   *
   * @var \Drupal\commerce_payment\PaymentGatewayPluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function setWeight($weight) {
    $this->weight = $weight;
    return $weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    return $this->getPluginCollection()->get($this->plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginId($plugin_id) {
    $this->plugin = $plugin_id;
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return [
      'configuration' => $this->getPluginCollection(),
    ];
  }

  /**
   * Gets the plugin collection that holds the payment gateway plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce_payment\PaymentGatewayPluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_payment_gateway');
      $this->pluginCollection = new PaymentGatewayPluginCollection($plugin_manager, $this->plugin, $this->configuration, $this->id);
    }
    return $this->pluginCollection;
  }

}
