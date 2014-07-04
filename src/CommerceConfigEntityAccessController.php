<?php

/**
 * @file
 * Contains \Drupal\commerce\CommerceConfigEntityAccessController.
 */

namespace Drupal\commerce;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Commerce entity type access controller.
 */
class CommerceConfigEntityAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {

    if ($operation === 'delete') {
      /** @var $entity \Drupal\commerce\CommerceEntityTypeInterface */
      $count = $entity->getContentCount();
      if ($count > 0) {
        return FALSE;
      }
    }

    /** @var $entity \Drupal\commerce\CommerceEntityTypeInterface */
    return parent::checkAccess($entity, $operation, $langcode, $account);
  }

}
