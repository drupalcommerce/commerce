
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

    $('fieldset#edit-order-history', context).drupalSetSummary(function (context) {
      var summary = $('#edit-created', context).val() ?
        Drupal.t('Created @date', { '@date' : $('#edit-created').val() }) :
        Drupal.t('New order');

      // Add the changed date to the summary if it's different from the created.
      if ($('#edit-created', context).val() != $('#edit-changed', context).val()) {
        summary += '<br />' + Drupal.t('Updated @date', { '@date' : $('#edit-changed').val() });
      }

      return summary;
    });
  }
};

})(jQuery);
