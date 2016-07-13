<?php

namespace Drupal\commerce_test\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Throws an exception.
 *
 * @Action(
 *   id = "commerce_test_throw_exception",
 *   label = @Translation("Throw an exception"),
 *   type = "commerce_test",
 *   category = "Commerce Test"
 * )
 */
class ThrowException extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    throw new \Exception("Test exception action.");
  }

}
