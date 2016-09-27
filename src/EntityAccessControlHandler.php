<?php

namespace Drupal\commerce;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler as CoreEntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides per-bundle entity CRUD permissions.
 */
class EntityAccessControlHandler extends CoreEntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $account = $this->prepareUser($account);
    /** @var \Drupal\Core\Access\AccessResult $result */
    $result = parent::checkAccess($entity, $operation, $account);

    if ($result->isNeutral()) {
      if ($entity instanceof EntityOwnerInterface) {
        $result = $this->checkEntityOwnerPermissions($entity, $operation, $account);
      }
      else {
        $result = $this->checkEntityPermissions($entity, $operation, $account);
      }
    }

    // Ensure that access is evaluated again when the entity changes.
    return $result->addCacheableDependency($entity);
  }

  /**
   * Checks the entity operation and bundle permissions.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    return AccessResult::allowedIfHasPermissions($account, [
      "$operation {$entity->getEntityTypeId()}",
      "$operation {$entity->bundle()} {$entity->getEntityTypeId()}",
    ], 'OR');
  }

  /**
   * Checks the entity operation and bundle permissions, with owners.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to check access.
   * @param string $operation
   *   The entity operation. Usually one of 'view', 'view label', 'update' or
   *   'delete'.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to check access.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  protected function checkEntityOwnerPermissions(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\Core\Entity\EntityInterface|\Drupal\user\EntityOwnerInterface $entity */
    if (($account->id() == $entity->getOwnerId())) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        "$operation own {$entity->getEntityTypeId()}",
        "$operation any {$entity->getEntityTypeId()}",
        "$operation own {$entity->bundle()} {$entity->getEntityTypeId()}",
        "$operation any {$entity->bundle()} {$entity->getEntityTypeId()}",
      ], 'OR');
    }
    else {
      $result = AccessResult::allowedIfHasPermissions($account, [
        "$operation any {$entity->getEntityTypeId()}",
        "$operation any {$entity->bundle()} {$entity->getEntityTypeId()}",
      ], 'OR');
    }

    return $result->cachePerUser();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral()) {
      $result = AccessResult::allowedIfHasPermissions($account, [
        'administer ' . $this->entityTypeId,
        'create ' . $entity_bundle . ' ' . $this->entityTypeId,
        'create any ' . $entity_bundle . ' ' . $this->entityTypeId,
        'create own ' . $entity_bundle . ' ' . $this->entityTypeId,
      ], 'OR');
    }

    return $result;
  }

}
