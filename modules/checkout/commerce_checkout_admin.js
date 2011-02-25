(function ($) {

/**
 * Add functionality to the checkout panes tabledrag enhanced table.
 *
 * This code is almost an exact copy of the code used for the block region and
 * weight settings form.
 */
Drupal.behaviors.paneDrag = {
  attach: function (context, settings) {
    // tableDrag is required for this behavior.
    if (typeof Drupal.tableDrag == 'undefined') {
      return;
    }

    var table = $('table#panes');
    var tableDrag = Drupal.tableDrag.panes; // Get the blocks tableDrag object.

    // Add a handler for when a row is swapped, update empty regions.
    tableDrag.row.prototype.onSwap = function(swappedRow) {
      checkEmptyPages(table, this);
    };

    // A custom message for the panes page specifically.
    Drupal.theme.tableDragChangedWarning = function () {
      return '<div class="messages warning">' + Drupal.theme('tableDragChangedMarker') + ' ' + Drupal.t("Changes to the checkout panes will not be saved until the <em>Save configuration</em> button is clicked.") + '</div>';
    };

    // Add a handler so when a row is dropped, update fields dropped into new regions.
    tableDrag.onDrop = function() {
      dragObject = this;

      var pageRow = $(dragObject.rowObject.element).prev('tr').get(0);
      var pageName = pageRow.className.replace(/([^ ]+[ ]+)*page-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
      var pageField = $('select.checkout-pane-page', dragObject.rowObject.element);

      if ($(dragObject.rowObject.element).prev('tr').is('.page-message')) {
        var weightField = $('select.checkout-pane-weight', dragObject.rowObject.element);
        var oldPageName = weightField[0].className.replace(/([^ ]+[ ]+)*checkout-pane-weight-([^ ]+)([ ]+[^ ]+)*/, '$2');

        if (!pageField.is('.checkout-pane-page-'+ pageName)) {
          pageField.removeClass('checkout-pane-page-' + oldPageName).addClass('checkout-pane-page-' + pageName);
          weightField.removeClass('checkout-pane-weight-' + oldPageName).addClass('checkout-pane-weight-' + pageName);
          pageField.val(pageName);
        }
      }
    };

    var checkEmptyPages = function(table, rowObject) {
      $('tr.page-message', table).each(function() {
        // If the dragged row is in this region, but above the message row, swap it down one space.
        if ($(this).prev('tr').get(0) == rowObject.element) {
          // Prevent a recursion problem when using the keyboard to move rows up.
          if ((rowObject.method != 'keyboard' || rowObject.direction == 'down')) {
            rowObject.swap('after', this);
          }
        }
        // This region has become empty
        if ($(this).next('tr').is(':not(.draggable)') || $(this).next('tr').size() == 0) {
          $(this).removeClass('page-populated').addClass('page-empty');
        }
        // This region has become populated.
        else if ($(this).is('.page-empty')) {
          $(this).removeClass('page-empty').addClass('page-populated');
        }
      });
    };
  }
};

})(jQuery);
