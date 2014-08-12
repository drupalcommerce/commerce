<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceConfigEntityAccessController.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Commerce entity type access controller.
 */
class CommerceConfigEntityAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    /** @var $entity \Drupal\commerce\CommerceEntityTypeInterface */
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
