<?php

/**
 * @file
 * Contains \Drupal\commerce_store\Form\StoreWarningTrait.
 */

namespace Drupal\commerce_store\Form;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a warning when no store has been configured.
 */
trait StoreWarningTrait {

  /**
   * Builds the warning message.
   *
   * @return bool
   *   True/False.
   */
  function buildStoreWarning() {
    // Check if there are stores.
    $stores = \Drupal::entityQuery('commerce_store');
    if ($stores->count()->execute() > 0) {
      return FALSE;
    }

    // Setup link options.
    $options = [
      'query' => [
        'destination' => \Drupal::service('path.current')->getPath(),
      ],
    ];

    // Build the link.
    $link_address = \Drupal::entityTypeManager()->getDefinition('commerce_store')->getLinkTemplate('add-page');
    $link = Link::fromTextAndUrl('Add a new store.', Url::fromUri('base:' . $link_address . '/default', $options))->toString();

    // Return the message.
    return \Drupal::translation()->translate('You have not yet configured a store. %url', ['%url' => $link]);
  }

}
