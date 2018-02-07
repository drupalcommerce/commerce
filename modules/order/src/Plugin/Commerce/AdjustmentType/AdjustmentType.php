<?php

namespace Drupal\commerce_order\Plugin\Commerce\AdjustmentType;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the adjustment type class.
 */
class AdjustmentType extends PluginBase implements AdjustmentTypeInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSingularLabel() {
    return $this->pluginDefinition['singular_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluralLabel() {
    return $this->pluginDefinition['plural_label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function hasUi() {
    return $this->pluginDefinition['has_ui'] == TRUE;
  }

}
