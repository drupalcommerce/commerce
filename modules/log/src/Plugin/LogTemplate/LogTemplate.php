<?php

namespace Drupal\commerce_log\Plugin\LogTemplate;

use Drupal\Component\Plugin\PluginBase;

class LogTemplate extends PluginBase implements LogTemplateInterface {

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
  public function getCategory() {
    return $this->pluginDefinition['category'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate() {
    return $this->pluginDefinition['template'];
  }

}
