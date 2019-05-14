<?php

namespace Drupal\commerce_order;

use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\user\UserInterface;

class AddressBookManager implements AddressBookManagerInterface {

  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new AddressBookManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeBundleInfo $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
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

    $bundles = $this->entityTypeBundleInfo->getBundleInfo('profile');
    if (!empty($bundles[$profile->bundle()]['multiple'])) {
      $address_book_profile = $profile->createDuplicate();
      $address_book_profile->setOwnerId($customer->id());
      $address_book_profile->unsetData('copy_to_address_book');
      $address_book_profile->save();
    }
    else {
      /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $address_book_profile = $profile_storage->loadDefaultByUser($customer, $profile->bundle());
      if (!$address_book_profile) {
        $address_book_profile = $profile_storage->create([
          'type' => $profile->bundle(),
          'uid' => $customer->id(),
        ]);
      }
      $address_book_profile->populateFromProfile($profile);
      $address_book_profile->save();
    }

    $profile->unsetData('copy_to_address_book');
    $profile->setData('address_book_profile_id', $address_book_profile->id());
    $profile->save();
  }

}
