<?php

namespace Drupal\commerce_number_pattern\Entity;

use Drupal\commerce\CommerceSinglePluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the number pattern entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_number_pattern",
 *   label = @Translation("Number pattern"),
 *   label_collection = @Translation("Number patterns"),
 *   label_singular = @Translation("number pattern"),
 *   label_plural = @Translation("number patterns"),
 *   label_count = @PluralTranslation(
 *     singular = "@count number pattern",
 *     plural = "@count number patterns",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\commerce_number_pattern\NumberPatternAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\commerce_number_pattern\Form\NumberPatternForm",
 *       "duplicate" = "Drupal\commerce_number_pattern\Form\NumberPatternForm",
 *       "edit" = "Drupal\commerce_number_pattern\Form\NumberPatternForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *       "reset-sequence" = "Drupal\commerce_number_pattern\Form\NumberPatternResetSequenceForm",
 *     },
 *     "local_task_provider" = {
 *       "default" = "Drupal\entity\Menu\DefaultEntityLocalTaskProvider",
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\commerce_number_pattern\NumberPatternRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_number_pattern\NumberPatternListBuilder",
 *   },
 *   admin_permission = "administer commerce_number_pattern",
 *   config_prefix = "commerce_number_pattern",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "targetEntityType",
 *     "plugin",
 *     "configuration",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/number-patterns/add",
 *     "edit-form" = "/admin/commerce/config/number-patterns/{commerce_number_pattern}/edit",
 *     "duplicate-form" = "/admin/commerce/config/number-patterns/{commerce_number_pattern}/duplicate",
 *     "delete-form" = "/admin/commerce/config/number-patterns/{commerce_number_pattern}/delete",
 *     "reset-sequence-form" = "/admin/commerce/config/number-patterns/{commerce_number_pattern}/reset-sequence",
 *     "collection" = "/admin/commerce/config/number-patterns"
 *   }
 * )
 */
class NumberPattern extends ConfigEntityBase implements NumberPatternInterface {

  /**
   * The number pattern ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The number pattern label.
   *
   * @var string
   */
  protected $label;

  /**
   * The target entity type ID.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * The plugin ID.
   *
   * @var string
   */
  protected $plugin = 'infinite';

  /**
   * The plugin configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * The plugin collection that holds the number pattern plugin.
   *
   * @var \Drupal\commerce\CommerceSinglePluginCollection
   */
  protected $pluginCollection;

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityTypeId() {
    return $this->targetEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntityTypeId($entity_type_id) {
    $this->targetEntityType = $entity_type_id;
    return $this;
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
    $this->configuration = [];
    $this->pluginCollection = NULL;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginConfiguration(array $configuration) {
    $this->configuration = $configuration;
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
   * {@inheritdoc}
   */
  public function set($property_name, $value) {
    // Invoke the setters to clear related properties.
    if ($property_name == 'plugin') {
      $this->setPluginId($value);
    }
    elseif ($property_name == 'configuration') {
      $this->setPluginConfiguration($value);
    }
    else {
      return parent::set($property_name, $value);
    }
  }

  /**
   * Gets the plugin collection that holds the number pattern plugin.
   *
   * Ensures the plugin collection is initialized before returning it.
   *
   * @return \Drupal\commerce\CommerceSinglePluginCollection
   *   The plugin collection.
   */
  protected function getPluginCollection() {
    if (!$this->pluginCollection) {
      $plugin_manager = \Drupal::service('plugin.manager.commerce_number_pattern');
      $this->pluginCollection = new CommerceSinglePluginCollection($plugin_manager, $this->plugin, $this->configuration, $this);
    }
    return $this->pluginCollection;
  }

}
