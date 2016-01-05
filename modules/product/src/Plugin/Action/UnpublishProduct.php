<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Plugin\Action\UnpublishProduct.
 */

namespace Drupal\commerce_product\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes a product.
 *
 * @Action(
 *   id = "unpublish_product_action",
 *   label = @Translation("Unpublish selected product"),
 *   type = "commerce_product"
 * )
 */
class UnpublishProduct extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $entity */
    $entity->setPublished(FALSE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_product\Entity\ProductInterface $object */
    $access = $object
      ->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
