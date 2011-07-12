<?php

/**
 * @file
 * Hooks provided by the Customer module.
 */


/**
 * Allows you to prepare customer profile data before it is saved.
 *
 * @param $profile
 *   The customer profile object to be saved.
 *
 * @see rules_invoke_all()
 */
function hook_commerce_customer_profile_presave($profile) {
  // No example.
}
