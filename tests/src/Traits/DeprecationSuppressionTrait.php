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
        "Render #post_render callbacks must be methods of a class that implements \Drupal\Core\Security\TrustedCallbackInterface or be an anonymous function. The callback was Drupal\address\Plugin\Field\FieldFormatter\AddressDefaultFormatter::postRender. Support for this callback implementation is deprecated in 8.8.0 and will be removed in Drupal 9.0.0. See https://www.drupal.org/node/2966725",
        "There is no `base theme` property specified in the mailsystem_test_theme.info.yml file. The optionality of the `base theme` property is deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. All Drupal 8 themes must add `base theme: stable` to their *.info.yml file for them to continue to work as-is in future versions of Drupal. Drupal 9 requires the `base theme` property to be specified. See https://www.drupal.org/node/3066038",
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
