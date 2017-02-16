<?php

namespace Drupal\commerce_test;

use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;

/**
 * Test availability checker.
 */
class TestAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $entity) {
    return $entity instanceof ProductVariationInterface && strpos($entity->getSku(), 'TEST_') !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    // Always return false.
    return FALSE;
  }

}
