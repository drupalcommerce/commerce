<?php

namespace Drupal\commerce_order\Access;

use Drupal\commerce_order\AddressBookInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks address book access.
 *
 * Intended to be combined with more specific (overview / CRUD) access checks.
 *
 * Requirements key: '_address_book_access'.
 */
class AddressBookAccessCheck implements AccessInterface {

  /**
   * The address book.
   *
   * @var \Drupal\commerce_order\AddressBookInterface
   */
  protected $addressBook;

  /**
   * Constructs a new AddressBookAccessCheck object.
   *
   * @param \Drupal\commerce_order\AddressBookInterface $address_book
   *   The address book.
   */
  public function __construct(AddressBookInterface $address_book) {
    $this->addressBook = $address_book;
  }

  /**
   * Checks address book access.
   *
   * Ensures that the user is logged in, and the address book UI is exposed.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account) {
    if ($account->isAnonymous()) {
      // Anonymous users can't have an address book.
      return AccessResult::forbidden()->addCacheContexts(['user.roles:authenticated']);
    }
    if (!$this->addressBook->hasUi()) {
      // No UI available.
      return AccessResult::forbidden()->addCacheTags(['config:profile_type_list']);
    }

    return AccessResult::allowed();
  }

}
