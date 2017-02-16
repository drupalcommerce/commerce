/**
 * @file
 * Defines behaviors for the checkout admin UI.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches the checkoutPaneOverview behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the checkoutPaneOverview behavior.
   *
   * @see Drupal.checkoutPaneOverview.attach
   */
  Drupal.behaviors.checkoutPaneOverview = {
    attach: function (context, settings) {
      $(context).find('table#checkout-pane-overview').once('checkout-pane-overview').each(function () {
        Drupal.checkoutPaneOverview.attach(this);
      });
    }
  };

  /**
   * Namespace for the checkout pane overview.
   *
   * @namespace
   */
  Drupal.checkoutPaneOverview = {

    /**
     * Attaches the checkoutPaneOverview behavior.
     *
     * @param {HTMLTableElement} table
     *   The table element for the overview.
     */
    attach: function (table) {
      var tableDrag = Drupal.tableDrag[table.id];

      // Add custom tabledrag callbacks.
      tableDrag.onDrop = this.onDrop;
      tableDrag.row.prototype.onSwap = this.onSwap;
    },

    /**
     * Updates the dropped row (Step dropdown, settings display).
     */
    onDrop: function () {
      var dragObject = this;
      var $rowElement = $(dragObject.rowObject.element);
      var regionRow = $rowElement.prevAll('tr.region-message').get(0);
      var regionName = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');
      var regionField = $rowElement.find('select.pane-step');

      // Keep the Step dropdown up to date.
      regionField.val(regionName);
      // Hide the settings in the disabled region.
      if (regionName == '_disabled') {
        $rowElement.find('.pane-configuration-summary').hide();
        $rowElement.find('.pane-configuration-edit-wrapper').hide();
      }
      else {
        $rowElement.find('.pane-configuration-summary').show();
        $rowElement.find('.pane-configuration-edit-wrapper').show();
      }
    },

    /**
     * Refreshes placeholder rows in empty regions while a row is being dragged.
     *
     * Copied from block.js.
     *
     * @param {HTMLElement} draggedRow
     *   The tableDrag rowObject for the row being dragged.
     */
    onSwap: function (draggedRow) {
      var rowObject = this;
      $(rowObject.table).find('tr.region-message').each(function () {
        var $this = $(this);
        // If the dragged row is in this region, but above the message row, swap
        // it down one space.
        if ($this.prev('tr').get(0) === rowObject.group[rowObject.group.length - 1]) {
          // Prevent a recursion problem when using the keyboard to move rows
          // up.
          if ((rowObject.method !== 'keyboard' || rowObject.direction === 'down')) {
            rowObject.swap('after', this);
          }
        }
        // This region has become empty.
        if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
          $this.removeClass('region-populated').addClass('region-empty');
        }
        // This region has become populated.
        else if ($this.is('.region-empty')) {
          $this.removeClass('region-empty').addClass('region-populated');
        }
      });
    }
  };

})(jQuery, Drupal);
