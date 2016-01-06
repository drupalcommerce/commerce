<?php

/**
 * @file
 * Contains helper commands for Commerce debugging.
 */

use Drupal\commerce\Plugin\Action\ActionDeriver;
use Symfony\Component\Yaml\Yaml;

/**
 * Implements hook_drush_command().
 */
function commerce_drush_command() {
  $items = [];
  $items['commerce_list_actions'] = [
    'aliases' => ['c_la'],
    'arguments' => [
      'id' => 'The action id. If absent, list all Commerce actions',
    ],
  ];
  return $items;
}

/**
 * Callback for commerce_list_actions command.
 */
function drush_commerce_list_actions($action = NULL) {
  /** @var \Drupal\Component\Plugin\CategorizingPluginManagerInterface $manager */

  $manager = \Drupal::service('plugin.manager.action');
  if ($action) {
    drush_print(Yaml::dump([$action => $manager->getDefinition($action, FALSE)]));
  }
  else {
    $definitions = [];
    foreach (ActionDeriver::getTypes() as $type) {
      $definitions[$type] = array_keys($manager->getDefinitionsByType($type));
    }
    drush_print(Yaml::dump($definitions, 3));
  }
}
