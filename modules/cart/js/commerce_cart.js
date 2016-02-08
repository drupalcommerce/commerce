/**
 * @file
 * Defines Javascript behaviors for the commerce cart module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.commerceCartBlock = {
    attach: function (context) {
      var $context = $(context),
          $cartClass = '.cart--cart-block',
          $cartContentClass = '.cart-block--contents',
          $cart = $context.find($cartClass),
          $cartButton = $context.find('.cart-block--link__expand'),
          $cartContents = $cart.find($cartContentClass);

      if ($cartContents.length > 0) {
        // Expand the block when the link is clicked.
        $cartButton.on('click', function (e) {
          e.preventDefault();


          // Get the shopping cart width + the offset to the left.
          var $windowWidth = $(window).width(),
              $cartOffsetLeft = $cart.offset().left,
              $cartWidth = $cartContents.width() + $cartOffsetLeft,
              $window = $(window);
          // If the cart goes out of the viewport we should align it right.
          if ($cartWidth > $windowWidth) {
            $cartContents.addClass('is-outside');
          }

          // Toggle the expanded class.
          $cartContents
            .toggleClass('cart-block--contents__expanded')
            .slideToggle('normal', function() {
              if ($cartContents.hasClass('cart-block--contents__expanded')) {
                // Get the shopping cart height + the offset to the top.
                resizecart($cartClass, $cartContentClass, $window);
                // When the window get's resized, we should recalculate.
                $(window).on('resize', function() {
                  resizecart($cartClass, $cartContentClass, $window);
                });
              }
              else {
                resetcart($cartClass, $cartContentClass);
              }
            });
        });
      }
    }
  };

  function resizecart(cartClass, contentClass, window) {
    var $cart= $(cartClass),
      $cartContent = $cart.find(contentClass),
      $cartOffsetTop = $cartContent.offset().top,
      $cartHeight = $cartContent.height() + $cartOffsetTop,
      $maxHeight = window.height() - $cartOffsetTop,
      $windowHeight = window.height();
    // If the cart size is bigger then the viewport, we should make it scrollable.
    if ($cartHeight > $windowHeight) {
      $cartContent.addClass('is-scrollable');
      $cartContent.css('max-height', $maxHeight + 'px');
    }
    else if ($cartHeight < $windowHeight && $cartContent.hasClass('is-scrollable')) {
      $cartContent.css('max-height', $maxHeight + 'px');
    }
  }

  function resetcart(cartClass, contentClass) {
    var $cart= $(cartClass),
      $cartContent = $cart.find(contentClass);

    $cartContent.removeAttr('style');
    $cartContent.removeClass('is-scrollable');
  }
})(jQuery, Drupal, drupalSettings);
