/**
 * @file
 * Defines Javascript behaviors for the cart form.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.commerceCartForm = {
    attach: function (context) {
      // Trigger the "Update" button when Enter is pressed in a quantity field.
      $('form .views-field-edit-quantity input.form-number', context)
        .once('commerce-cart-edit-quantity')
        .keydown(function (event) {
          if (event.keyCode === 13) {
            // Prevent the browser default ("Remove") from being triggered.
            event.preventDefault();
            $(':input#edit-submit', $(this).parents('form')).click();
          }
        });
    }
  };
})(jQuery, Drupal, drupalSettings);
