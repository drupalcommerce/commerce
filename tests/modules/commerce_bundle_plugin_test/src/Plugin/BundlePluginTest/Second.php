<?php

namespace Drupal\commerce_bundle_plugin_test\Plugin\BundlePluginTest;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the second bundle plugin.
 *
 * @BundlePluginTest(
 *   id = "second",
 *   label = @Translation("Second"),
 * )
 */
class Second extends PluginBase implements BundlePluginTestInterface {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['second_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE);

    return $fields;
  }

}
