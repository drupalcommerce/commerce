/**
 * @file
 * Condition UI behaviors.
 */

(function ($, window, Drupal) {

  'use strict';

  /**
   * Provides the summary information for the condition vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the condition summaries.
   */
  Drupal.behaviors.conditionSummary = {
    attach: function () {
      $('.vertical-tabs__pane').each(function () {
        $(this).drupalSetSummary(function (context) {
          if ($(context).find('input.enable:checked').length) {
            return Drupal.t('Restricted');
          }
          else {
            return Drupal.t('Not restricted');
          }
        });
      });
    }
  };

})(jQuery, window, Drupal);
