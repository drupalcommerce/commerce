<?php

namespace Drupal\commerce_bundle_plugin_test\Plugin\BundlePluginTest;

use Drupal\commerce\BundleFieldDefinition;
use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the first bundle plugin.
 *
 * @BundlePluginTest(
 *   id = "first",
 *   label = @Translation("First"),
 * )
 */
class First extends PluginBase implements BundlePluginTestInterface {

  /**
   * {@inheritdoc}
   */
  public function buildFieldDefinitions() {
    $fields = [];
    $fields['first_mail'] = BundleFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setRequired(TRUE);

    return $fields;
  }

}
