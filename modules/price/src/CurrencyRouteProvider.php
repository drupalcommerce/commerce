<?php

namespace Drupal\commerce_price;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;

/**
 * Provides routes for the Currency entity.
 */
class CurrencyRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    // Replace the "Add currency" title with "Add custom currency".
    // The t() function is used to ensure the string is picked up for
    // translation, even though _title is supposed to be untranslated.
    $route->setDefault('_title_callback', '');
    $route->setDefault('_title', t('Add custom currency')->getUntranslatedString());

    return $route;
  }

}
