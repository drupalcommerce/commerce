<?php

/**
 * @file
 * The controller for the customer profile entity containing the CRUD operations.
 */

/**
 * The controller class for customer profiles contains methods for the profile
 * CRUD operations. The load method is inherited from the default controller.
 */
class CommerceCustomerProfileEntityController extends DrupalDefaultEntityController {

  /**
   * Create a default customer profile.
   *
   * @param $type
   *   The machine-readable type of the customer profile.
   * @param $uid
   *   The uid of the user the customer profile is for.
   *
   * @return
   *   A customer profile object with all default fields initialized.
   */
  public function create($type = '', $uid = 0) {
    return (object) array(
      'profile_id' => '',
      'revision_id' => '',
      'type' => $type,
      'uid' => $uid,
      'status' => 1,
      'created' => '',
      'changed' => '',
    );
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
   *
   * @return
   *   The saved customer profile object.
   */
  public function save($profile) {
    $transaction = db_transaction();

    try {
      global $user;

      // Determine if we will be inserting a new profile.
      $profile->is_new = empty($profile->profile_id);

      // Set the timestamp fields.
      if (empty($profile->created)) {
        $profile->created = REQUEST_TIME;
      }

      $profile->changed = REQUEST_TIME;

      $profile->revision_timestamp = REQUEST_TIME;
      $update_profile = TRUE;

      // Give modules the opportunity to prepare field data for saving.
      module_invoke_all('entity_presave', $profile, 'commerce_customer_profile');
      rules_invoke_all('commerce_customer_profile_presave', $profile);
      field_attach_presave('commerce_customer_profile', $profile);

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

      // When saving a new profile revision, unset any existing $profile->revision_id
      // to ensure a new revision will actually be created and store the old
      // revision ID in a separate property for profile hook implementations.
      if (!$profile->is_new && !empty($profile->revision) && $profile->revision_id) {
        $profile->old_revision_id = $profile->revision_id;
        unset($profile->revision_id);
      }

      // If this is a new profile...
      if ($profile->is_new) {
        // Save the new profile and fields.
        drupal_write_record('commerce_customer_profile', $profile);

        // Save the initial revision.
        $this->saveRevision($profile, $user->uid);

        $op = 'insert';
      }
      else {
        // Save the updated profile and fields.
        drupal_write_record('commerce_customer_profile', $profile, 'profile_id');

        // If a new profile revision was requested, save a new record for that;
        // otherwise, update the profile revision record that matches the value
        // of $profile->revision_id.
        if (!empty($profile->revision)) {
          $this->saveRevision($profile, $user->uid);
        }
        else {
          $this->saveRevision($profile, $user->uid, TRUE);
          $update_profile = FALSE;
        }

        $op = 'update';
      }

      // If the revision ID is new or updated, save it to the profile.
      if ($update_profile) {
        db_update('commerce_customer_profile')
          ->fields(array('revision_id' => $profile->revision_id))
          ->condition('profile_id', $profile->profile_id)
          ->execute();
      }

      // Save fields.
      $function = 'field_attach_' . $op;
      $function('commerce_customer_profile', $profile);

      module_invoke_all('commerce_customer_profile_' . $op, $profile);
      module_invoke_all('entity_' . $op, $profile, 'commerce_customer_profile');
      rules_invoke_event('commerce_customer_profile_' . $op, $profile);

      // Clear internal properties.
      unset($profile->is_new);
      $this->resetCache();

      // Ignore slave server temporarily to give time for the
      // saved profile to be propagated to the slave.
      db_ignore_slave();

      return $profile;
    }
    catch (Exception $e) {
      $transaction->rollback();
      watchdog_exception('commerce_customer', $e);
      throw $e;
    }
  }

  /**
   * Saves a customer profile revision.
   *
   * @param $profile
   *   The fully loaded customer profile object.
   * @param $uid
   *   The user's uid for the current revision.
   * @param $update
   *   TRUE or FALSE indicating whether or not the existing revision should be
   *     updated instead of a new one created.
   */
  function saveRevision($profile, $uid, $update = FALSE) {
    // Hold on to the profile's original uid but swap in the revision's uid for
    // the momentary write.
    $temp_uid = $profile->uid;
    $profile->uid = $uid;

    // Update the existing revision if specified.
    if ($update) {
      drupal_write_record('commerce_customer_profile_revision', $profile, 'revision_id');
    }
    else {
      // Otherwise insert a new revision. This will automatically update $profile
      // to include the revision_id.
      drupal_write_record('commerce_customer_profile_revision', $profile);
    }

    // Reset the profile's uid to the original value.
    $profile->uid = $temp_uid;
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

  /**
   * Deletes multiple customer profiles by ID.
   *
   * @param $profile_ids
   *   An array of profile IDs to delete.
   *
   * @return
   *   TRUE on success, FALSE otherwise.
   */
  public function delete($profile_ids) {
    if (!empty($profile_ids)) {
      $profiles = $this->load($profile_ids, array());

      // TODO: We'll need to ensure profiles on orders cannot be deleted when we
      // have orders implemented.

      $transaction = db_transaction();

      try {
        db_delete('commerce_customer_profile')
          ->condition('profile_id', $profile_ids, 'IN')
          ->execute();

        foreach ($profiles as $profile_id => $profile) {
          module_invoke_all('commerce_customer_profile_delete', $profile);
          field_attach_delete('commerce_customer_profile', $profile);
          rules_invoke_event('commerce_customer_profile_delete', $profile);
        }

        // Ignore slave server temporarily to give time for the
        // saved profile to be propagated to the slave.
        db_ignore_slave();
      }
      catch (Exception $e) {
        $transaction->rollback();
        watchdog_exception('commerce_customer', $e);
        throw $e;
      }

      // Clear the page and block and customer_profile_load_multiple caches.
      cache_clear_all();
      $this->resetCache();
    }

    return TRUE;
  }
}
