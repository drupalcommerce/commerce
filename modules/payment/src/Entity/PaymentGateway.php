<?php

namespace Drupal\commerce_payment\Entity;

use Drupal\commerce_payment\PaymentGatewayPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Config\ConfigNameException;
use Drupal\Core\Entity\EntityStorageInterface;

/**
 * Defines the payment gateway entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_payment_gateway",
 *   label = @Translation("Payment gateway"),
 *   label_singular = @Translation("Payment gateway"),
 *   label_plural = @Translation("Payment gateways"),
 *   label_count = @PluralTranslation(
 *     singular = "@count payment gateway",
 *     plural = "@count payment gateways",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\commerce_payment\PaymentGatewayListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_payment\Form\PaymentGatewayForm",
 *       "edit" = "Drupal\commerce_payment\Form\PaymentGatewayForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer payment gateways",
 *   config_prefix = "commerce_payment_gateway",
 *   bundle_of = "commerce_payment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "weight",
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

  /**
   * Acts on an entity before the presave hook is invoked.
   *
   * Used before the entity is saved and before invoking the presave hook.
   *
   * Ensure that config entities which are bundles of other entities cannot have
   * their ID changed.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage object.
   *
   * @throws \Drupal\Core\Config\ConfigNameException
   *   Thrown when attempting to rename a bundle entity.
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Only handle renames, not creations.
    if (!$this->isNew() && $this->getOriginalId() !== $this->id()) {
      $bundle_type = $this->getEntityType();
      $bundle_of = $bundle_type->getBundleOf();
      if (!empty($bundle_of)) {
        throw new ConfigNameException("The machine name of the '{$bundle_type->getLabel()}' bundle cannot be changed.");
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    $bundle_of = $this->getEntityType()->getBundleOf();
    if (!$update) {
      \Drupal::service('entity_bundle.listener')->onBundleCreate($this->id(), $bundle_of);
    }
    else {
      $entity_field_manager = \Drupal::getContainer()->get('entity_field.manager');
      // Entity bundle field definitions may depend on bundle settings.
      $entity_field_manager->clearCachedFieldDefinitions();
      $entity_field_manager->clearCachedBundles();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      \Drupal::service('entity_bundle.listener')->onBundleDelete($entity->id(), $entity->getEntityType()->getBundleOf());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
  }

}
