<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the Coupon entity.
 */
class CouponRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    $route = parent::getAddFormRoute($entity_type);
    $route->setOption('parameters', [
      'commerce_promotion' => [
        'type' => 'entity:commerce_promotion',
      ],
    ]);
    // Coupons can be created if the parent promotion can be updated.
    $requirements = $route->getRequirements();
    unset($requirements['_entity_create_access']);
    $requirements['_entity_access'] = 'commerce_promotion.update';
    $route->setRequirements($requirements);

    return $route;
  }

}
