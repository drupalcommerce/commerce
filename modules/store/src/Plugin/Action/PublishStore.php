<?php

namespace Drupal\commerce_store\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Publishes a store.
 *
 * @Action(
 *   id = "commerce_publish_store",
 *   label = @Translation("Publish selected store"),
 *   type = "commerce_store"
 * )
 */
class PublishStore extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\commerce_store\Entity\StoreInterface $entity */
    $entity->setPublished(TRUE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_store\Entity\StoreInterface $object */
    $result = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
