<?php

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

class AddressBook implements AddressBookInterface {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * The profile type storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $profileTypeStorage;

  /**
   * Constructs a new AddressBook object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeBundleInfo $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->profileStorage = $entity_type_manager->getStorage('profile');
    $this->profileTypeStorage = $entity_type_manager->getStorage('profile_type');
  }

  /**
   * {@inheritdoc}
   */
  public function allowsMultiple($profile_type_id) {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('profile');

    return !empty($bundle_info[$profile_type_id]['multiple']);
  }

  /**
   * {@inheritdoc}
   */
  public function hasUi() {
    $profile_types = $this->loadTypes();
    if (empty($profile_types)) {
      // No profile types available.
      return FALSE;
    }
    elseif (count($profile_types) === 1) {
      $profile_type = reset($profile_types);
      if (!$profile_type->allowsMultiple()) {
        // There is only one profile type, and it only allows one profile per
        // customer. No point in presenting an "address book" for one address.
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function loadTypes() {
    /** @var \Drupal\profile\Entity\ProfileTypeInterface[] $profile_types */
    $profile_types = $this->profileTypeStorage->loadByProperties([
      'third_party_settings.commerce_order.customer_profile_type' => TRUE,
    ]);
    return $profile_types;
  }

  /**
   * {@inheritdoc}
   */
  public function loadAll(UserInterface $customer, $profile_type_id, array $available_countries = []) {
    if ($customer->isAnonymous()) {
      return [];
    }

    $profiles = $this->profileStorage->loadMultipleByUser($customer, $profile_type_id, TRUE);
    // Filter out profiles with unavailable countries.
    foreach ($profiles as $profile_id => $profile) {
      if (!$this->isAvailable($profile, $available_countries)) {
        unset($profiles[$profile_id]);
      }
    }

    return $profiles;
  }

  /**
   * {@inheritdoc}
   */
  public function load(UserInterface $customer, $profile_type_id, array $available_countries = []) {
    if ($customer->isAnonymous()) {
      return NULL;
    }

    $profile = $this->profileStorage->loadByUser($customer, $profile_type_id);
    if ($profile && !$this->isAvailable($profile, $available_countries)) {
      $profile = NULL;
    }

    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function needsCopy(ProfileInterface $profile) {
    return (bool) $profile->getData('copy_to_address_book', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function copy(ProfileInterface $profile, UserInterface $customer) {
    if ($customer->isAnonymous()) {
      return;
    }

    // Check if there is an existing address book profile to update.
    // This can happen in two scenarios:
    // 1) The profile was already copied to the address book once.
    // 2) The customer is only allowed to have a single address book profile.
    $address_book_profile = NULL;
    $address_book_profile_id = $profile->getData('address_book_profile_id');
    if ($address_book_profile_id) {
      /** @var \Drupal\profile\Entity\ProfileInterface $address_book_profile */
      $address_book_profile = $this->profileStorage->load($address_book_profile_id);
    }
    if (!$address_book_profile && !$this->allowsMultiple($profile->bundle())) {
      $address_book_profile = $this->load($customer, $profile->bundle());
    }

    if ($address_book_profile) {
      $address_book_profile->populateFromProfile($profile);
      $address_book_profile->save();
    }
    else {
      $address_book_profile = $profile->createDuplicate();
      $address_book_profile->setOwnerId($customer->id());
      $address_book_profile->unsetData('copy_to_address_book');
      $address_book_profile->save();
    }

    $profile->unsetData('copy_to_address_book');
    $profile->setData('address_book_profile_id', $address_book_profile->id());
    $profile->save();
  }

  /**
   * Checks if the given profile is available.
   *
   * If the list of available countries is restricted, the profile address
   * is checked against it.
   *
   * @param \Drupal\profile\Entity\ProfileInterface $profile
   *   The profile.
   * @param array $available_countries
   *   List of country codes. If empty, all countries will be available.
   *
   * @return bool
   *   TRUE if the profile is available, FALSE otherwise.
   */
  protected function isAvailable(ProfileInterface $profile, array $available_countries) {
    if (empty($available_countries)) {
      return TRUE;
    }
    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem $address */
    $address = $profile->get('address')->first();
    $country_code = $address ? $address->getCountryCode() : 'ZZ';

    return in_array($country_code, $available_countries);
  }

}
