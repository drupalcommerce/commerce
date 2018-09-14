<?php

namespace Drupal\commerce_store\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a store.
 *
 * @Action(
 *   id = "commerce_unpublish_store",
 *   label = @Translation("Unpublish selected store"),
 *   type = "commerce_store"
 * )
 */
class UnpublishStore extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\commerce_store\Entity\StoreInterface $entity */
    $entity->setUnpublished();
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_store\Entity\StoreInterface $object */
    $access = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
