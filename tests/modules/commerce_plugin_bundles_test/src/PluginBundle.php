<?php

namespace Drupal\commerce_plugin_bundles_test;

use Drupal\commerce\BundlePluginInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Class PluginBundle.
 */
class PluginBundle extends PluginBase implements BundlePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    return [];
  }

}
