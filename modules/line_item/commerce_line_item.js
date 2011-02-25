
(function($) {

  /**
   * Trigger the Update button when hitting the enter key.
   */
  Drupal.behaviors.commerceLineItemForm = {
    attach: function (context, settings) {
      // Click the update button, not the remove button on enter key if we are
      // on a text field.
      $('div.commerce-line-item-views-form > form input.form-text', context).keydown(function(event) {
        if (event.keyCode === 13) {
          // Prevent browser's default submit from being clicked.
          event.preventDefault();
          $('input#edit-update', $(this).parents('form')).click();
        }
      });
    }
  }

})(jQuery);
