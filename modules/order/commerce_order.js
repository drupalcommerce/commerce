// $Id$

(function ($) {

Drupal.behaviors.orderFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-order-status', context).drupalSetSummary(function (context) {
      // If the status has been changed, indicate the original status.
      if ($('#edit-status').val() != $('#edit-status-original').val()) {
        return Drupal.t('From @title', { '@title' : Drupal.settings.status_titles[$('#edit-status-original').val()] }) + '<br />' + Drupal.t('To @title', { '@title' : Drupal.settings.status_titles[$('#edit-status').val()] });
      }
      else {
        return Drupal.settings.status_titles[$('#edit-status').val()];
      }
    });

    $('fieldset#edit-user', context).drupalSetSummary(function (context) {
      var name = $('#edit-name').val() || Drupal.settings.anonymous,
        mail = $('#edit-mail').val();
      return mail ?
        Drupal.t('Owned by @name', { '@name' : name }) + '<br />' + mail :
        Drupal.t('Owned by @name', { '@name': name });
    });

    $('fieldset#edit-order-log', context).drupalSetSummary(function (context) {
      return $('#edit-log', context).val() ?
        Drupal.t('Notes added') :
        Drupal.t('No notes added');
    });
  }
};

})(jQuery);
