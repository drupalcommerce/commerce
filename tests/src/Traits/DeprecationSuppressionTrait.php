<?php

namespace Drupal\Tests\commerce\Traits;

/**
 * Enables suppression of select deprecation messages during testing.
 *
 * This trait creates a custom error handler able to suppress a specific set
 * of deprecation errors, while allowing the rest to surface. It's useful to
 * ignore certain errors temporarily, such as those from dependencies, or those
 * whose fix has been intentionally postponed until a support is dropped for
 * legacy drupal versions.
 *
 * @package Drupal\Tests\commerce\Traits
 */
trait DeprecationSuppressionTrait {

  /**
   * Sets an error handler to suppress specified deprecation messages.
   */
  protected function setErrorHandler() {
    $previous_error_handler = set_error_handler(function ($severity, $message, $file, $line, $context) use (&$previous_error_handler) {

      $skipped_deprecations = [
        // @see https://www.drupal.org/project/address/issues/3089266
        'Theme functions are deprecated in drupal:8.0.0 and are removed from drupal:10.0.0. Use Twig templates instead of theme_inline_entity_form_entity_table(). See https://www.drupal.org/node/1831138',
      ];

      if (!in_array($message, $skipped_deprecations, TRUE)) {
        return $previous_error_handler($severity, $message, $file, $line, $context);
      }
    }, E_USER_DEPRECATED);
  }

  /**
   * Restores the original error handler.
   */
  protected function restoreErrorHandler() {
    restore_error_handler();
  }

}
