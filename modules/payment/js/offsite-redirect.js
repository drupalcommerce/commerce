/**
 * @file
 * Defines behaviors for the payment redirect form.
 */
(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the commercePaymentRedirect behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the commercePaymentRedirect behavior.
   */
  Drupal.behaviors.commercePaymentRedirect = {
    attach: function (context) {
      $('.payment-redirect-form', context).find('input[type="submit"]').trigger('click');
    }
  };

})(jQuery, Drupal, drupalSettings);
