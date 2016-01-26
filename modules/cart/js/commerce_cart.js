/**
 * @file
 * Defines Javascript behaviors for the commerce cart module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.commerceCartBlock = {
    attach: function (context) {
      var $context = $(context);
      var $cart = $context.find('.cart--cart-block');
      var $cartContents = $cart.find('.cart-block--contents');

      if ($cartContents.length > 0) {
        $cart.click(function (e) {
          e.preventDefault();
          $cartContents
            .toggleClass('cart-block--contents__expanded')
            .slideToggle();
        });
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
