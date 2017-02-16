<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;

/**
 * Provides routes for the Promotion entity.
 */
class PromotionRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    // Promotions use the edit-form route as the canonical route.
    // @todo Remove this when #2479377 gets fixed.
    return $this->getEditFormRoute($entity_type);
  }

}
