;(function($) {

  /**
   * Automatically submit the payment redirect form.
   */
  Drupal.behaviors.commercePayment = {
    attach: function (context, settings) {
      $('div.payment-redirect-form form', context).submit();
    }
  }
})(jQuery);
