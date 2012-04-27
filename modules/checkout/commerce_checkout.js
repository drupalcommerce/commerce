;(function($) {

  /**
   * Disable the continue buttons in the checkout process once they are clicked
   * and provide a notification to the user.
   */
  Drupal.behaviors.commerceCheckout = {
    attach: function (context, settings) {
      // When the buttons to move from page to page in the checkout process are
      // clicked we disable them so they are not accidently clicked twice.
      $('input.checkout-continue:not(.checkout-processed)', context).addClass('checkout-processed').click(function() {
        var $this = $(this);
        $this.clone().insertAfter(this).attr('disabled', true).next().removeClass('element-invisible');
        $this.hide();
      });
    }
  }

})(jQuery);
