<?php

namespace Drupal\commerce_log\Plugin\LogCategory;

use Drupal\Core\Plugin\PluginBase;

class LogCategory extends PluginBase implements LogCategoryInterface {

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
  public function getEntityTypeId() {
    return $this->pluginDefinition['entity_type'];
  }

}
