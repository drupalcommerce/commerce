<?php

namespace Drupal\commerce_order;

use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Copies profiles to the customer's address book.
 */
interface AddressBookManagerInterface {

  /**
   * Checks if the profile needs to be copied to the customer's address book.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   *
   * @return bool
   *   TRUE if the profile needs to be copied to the customer's address book,
   *   FALSE otherwise.
   */
  public function needsCopy(ProfileInterface $profile);

  /**
   * Copies the profile to the customer's address book.
   *
   * If the customer is allowed to have multiple profiles of this type,
   * the given profile is duplicated and assigned to them.
   * Otherwise, the default profile is loaded (created if missing), and then
   * updated with values from the given profile.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   */
  public function copy(ProfileInterface $profile, UserInterface $customer);

}
