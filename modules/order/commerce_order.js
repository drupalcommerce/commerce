// $Id$

(function ($) {

Drupal.behaviors.orderFieldsetSummaries = {
  attach: function (context) {
    $('fieldset#edit-update-notes', context).drupalSetSummary(function (context) {
      return $('#edit-log', context).val() ?
        Drupal.t('Notes added') :
        Drupal.t('No notes added');
    });

    $('fieldset#edit-creation', context).drupalSetSummary(function (context) {
      var name = $('#edit-name').val() || Drupal.settings.anonymous,
        date = $('#edit-date').val();
      return date ?
        Drupal.t('Owned by @name', { '@name' : name }) + '<br />' + Drupal.t('Created @date', { '@date': date }) :
        Drupal.t('Owned by @name', { '@name': name });
    });
  }
};

})(jQuery);
