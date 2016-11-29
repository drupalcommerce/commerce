<?php

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the Order entity.
 */
class OrderRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCanonicalRoute($entity_type);
    // Replace the 'full' view mode with the 'admin' view mode.
    $route->setDefault('_entity_view', 'commerce_order.admin');

    return $route;
  }

}
