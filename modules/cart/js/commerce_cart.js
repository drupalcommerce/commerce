/**
 * @file
 * Defines Javascript behaviors for the commerce cart module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.commerceCartBlock = {
    attach: function (context) {
      var $context = $(context),
          $cart = $context.find('.cart--cart-block'),
          $cartButton = $context.find('.cart-block--link__expand'),
          $cartContents = $cart.find('.cart-block--contents'),
          $windowHeight = $(window).height(),
          $windowWidth = $(window).width();

      if ($cartContents.length > 0) {
        // Get the shopping cart height + the offset to the top.
        var $cartOffsetTop = $cartContents.offset().top,
            $cartHeight = $cartContents.height() + $cartOffsetTop;
        // If the cart size is bigger then the viewport, we should make it scrollable. And set a max-height.
        if ($cartHeight >= $windowHeight) {
          $cartContents.addClass('is-scrollable');
        }

        // Get the shopping cart width + the offset to the left.
        var $cartOffsetLeft = $cart.offset().left,
            $cartWidth = $cartContents.width() + $cartOffsetLeft;
        // If the cart goes out of the viewport we should align it right.
        if ($cartWidth > $windowWidth) {
          $cartContents.addClass('is-outside');
        }

        // Expand the block when the link is clicked.
        $cartButton.click(function (e) {
          e.preventDefault();
          $cartContents
            .toggleClass('cart-block--contents__expanded')
            .slideToggle();
        });
      }

    }
  };
})(jQuery, Drupal, drupalSettings);
