<?php

namespace Drupal\commerce_order;

use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

/**
 * Represents a customer's address book.
 *
 * An address book is a collection of profile entities belonging to a
 * customer. Profiles can be of different types, and customers have a
 * default profile for each type.
 *
 * Anonymous profiles (uid: 0) belong to a parent entity (e.g. an order or
 * payment method) and maintain a record of customer information for
 * use in that entity's context. They are not part of an address book but
 * can be copied to be saved to a customer's address book.
 *
 * We separate profiles in this manner to ensure data integrity for the
 * orders, payment methods, etc., preventing the customer information
 * saved to them from being edited or deleted in other contexts.
 */
interface AddressBookInterface {

  /**
   * Gets whether a customer can have multiple profiles of this type.
   *
   * @param string $profile_type_id
   *   The profile type ID.
   *
   * @return bool
   *   TRUE if a customer can have multiple profiles of this type, FALSE
   *   otherwise.
   */
  public function allowsMultiple($profile_type_id);

  /**
   * Gets whether the address book has a UI exposed.
   *
   * Usually presented as an "Address book" tab on user pages, replacing
   * profile module's per-profile-type tabs.
   *
   * @return bool
   *   TRUE if the address book has a UI exposed, FALSE otherwise.
   */
  public function hasUi();

  /**
   * Loads the profile types used by the address book.
   *
   * Only customer profile types are included.
   *
   * @return \Drupal\profile\Entity\ProfileTypeInterface[]
   *   The profile types, keyed by profile type ID.
   */
  public function loadTypes();

  /**
   * Loads all profiles for the given customer.
   *
   * Ensures that the loaded profiles are available, by filtering
   * them against $available_countries.
   *
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   * @param string $profile_type_id
   *   The profile type ID.
   * @param array $available_countries
   *   List of country codes. If empty, all countries will be available.
   *
   * @return \Drupal\profile\Entity\ProfileInterface[]
   *   The available profiles, keyed by profile ID.
   */
  public function loadAll(UserInterface $customer, $profile_type_id, array $available_countries = []);

  /**
   * Loads the customer's profile.
   *
   * Takes the default profile, if found.
   * Otherwise falls back to the newest published profile.
   *
   * Primarily used for profile types which only allow a
   * single profile per user.
   *
   * Ensures that the loaded profile is available, by filtering it
   * against $available_countries. If the loaded profile is not
   * available, NULL will be returned instead.
   *
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   * @param string $profile_type_id
   *   The profile type ID.
   * @param array $available_countries
   *   List of country codes. If empty, all countries will be available.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   The profile, or NULL if none found.
   */
  public function load(UserInterface $customer, $profile_type_id, array $available_countries = []);

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
   * the given profile will be duplicated and assigned to them.
   * If the given profile was already copied to the customer's address book
   * once, the matching address book profile will be updated instead.
   *
   * If the customer is only allowed to have a single profile of this type,
   * the default profile will be loaded (created if missing) and updated.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   * @param \Drupal\user\UserInterface $customer
   *   The customer.
   */
  public function copy(ProfileInterface $profile, UserInterface $customer);

}
