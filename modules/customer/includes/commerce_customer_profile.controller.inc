<?php

/**
 * @file
 * The controller for the customer profile entity containing the CRUD operations.
 */

/**
 * The controller class for customer profiles contains methods for the profile
 * CRUD operations. The load method is inherited from the default controller.
 */
class CommerceCustomerProfileEntityController extends DrupalCommerceEntityController {

  /**
   * Create a default customer profile.
   *
   * @param array $values
   *   An array of values to set, keyed by property name.
   *
   * @return
   *   A customer profile object with all default fields initialized.
   */
  public function create(array $values = array()) {
    return (object) ($values + array(
      'profile_id' => '',
      'revision_id' => '',
      'type' => '',
      'uid' => 0,
      'status' => 1,
      'created' => '',
      'changed' => '',
    ));
  }

  /**
   * Saves a customer profile.
   *
   * When saving a profile without an ID, this function will create a new
   * profile at that time. Subsequent profiles that should be saved as new
   * revisions should set $profile->revision to TRUE and include a log string in
   * $profile->log.
   *
   * @param $profile
   *   The full customer profile object to save.
   * @param $transaction
   *   An optional transaction object.
   *
   * @return
   *   The saved customer profile object.
   */
  public function save($profile, DatabaseTransaction $transaction = NULL) {
    if (!isset($transaction)) {
      $transaction = db_transaction();
      $started_transaction = TRUE;
    }

    try {
      global $user;

      // Determine if we will be inserting a new profile.
      $profile->is_new = empty($profile->profile_id);

      // Set the timestamp fields.
      if ($profile->is_new && empty($profile->created)) {
        $profile->created = REQUEST_TIME;
      }

      $profile->changed = REQUEST_TIME;

      $profile->revision_uid = $user->uid;
      $profile->revision_timestamp = REQUEST_TIME;

      if ($profile->is_new || !empty($profile->revision)) {
        // When inserting either a new profile or revision, $profile->log must
        // be set because {commerce_customer_profile_revision}.log is a text
        // column and therefore cannot have a default value. However, it might
        // not be set at this point, so we ensure that it is at least an empty
        // string in that case.
        if (!isset($profile->log)) {
          $profile->log = '';
        }
      }
      elseif (empty($profile->log)) {
        // If we are updating an existing profile without adding a new revision,
        // we need to make sure $profile->log is unset whenever it is empty. As
        // long as $profile->log is unset, drupal_write_record() will not attempt
        // to update the existing database column when re-saving the revision.
        unset($profile->log);
      }

      return parent::save($profile, $transaction);
    }
    catch (Exception $e) {
      if (!empty($started_transaction)) {
        $transaction->rollback();
        watchdog_exception($this->entityType, $e);
      }
      throw $e;
    }
  }

  /**
   * Unserializes the data property of loaded customer profiles.
   */
  public function attachLoad(&$queried_profiles, $revision_id = FALSE) {
    foreach ($queried_profiles as $profile_id => &$profile) {
      $profile->data = unserialize($profile->data);
    }

    // Call the default attachLoad() method. This will add fields and call
    // hook_commerce_customer_profile_load().
    parent::attachLoad($queried_profiles, $revision_id);
  }
}
