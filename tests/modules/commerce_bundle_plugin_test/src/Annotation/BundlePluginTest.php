<?php

namespace Drupal\commerce_bundle_plugin_test\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the BundlePluginTest annotation object.
 *
 * Plugin namespace: Plugin\BundlePluginTest.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class BundlePluginTest extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin label.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

}
