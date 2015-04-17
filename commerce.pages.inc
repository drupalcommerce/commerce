<?php

/**
 * @file
 * Callbacks for store add pages.
 */

use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;

/**
 * Prepares variables for the list of available bundles.
 *
 * Default template: commerce-add-list.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - bundle_type: The entity type of the bundles.
 *   - bundles: An array of bundles.
 *   - form_route_name: The add form route.
 */
function template_preprocess_commerce_add_list(&$variables) {
  $bundle_type = \Drupal::entityManager()->getDefinition($variables['bundle_type']);
  $variables += [
    'class' => str_replace('_', '-', $bundle_type->getBundleOf()) . '-add-list',
    // There is no link template for the add form, guess the route name.
    'create_bundle_url' => Url::fromRoute('entity.' . $bundle_type->id() . '.add_form'),
    'bundle_type_label' => $bundle_type->getLowercaseLabel(),
  ];

  foreach ($variables['bundles'] as $bundle) {
    $url = Url::fromRoute($variables['form_route_name'], [$bundle->getEntityTypeId() => $bundle->id()]);
    $variables['bundles'][$bundle->id()] = [
      'bundle' => $bundle->id(),
      'add_link' => \Drupal::l($bundle->label(), $url),
      'description' => Xss::filterAdmin($bundle->getDescription()),
    ];
  }
}
