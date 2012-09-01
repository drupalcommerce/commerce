(function($) {

Drupal.behaviors.customerFieldsetSummaries = {
  attach: function (context, settings) {
    $('fieldset#edit-user', context).drupalSetSummary(function (context) {
      var name = $('#edit-name').val() || Drupal.settings.anonymous;

      return Drupal.t('Owned by @name', { '@name': name });
    });

    $('fieldset#edit-profile-status', context).drupalSetSummary(function (context) {
      return ($('input[@name=status]:checked').val() == 0) ?
        Drupal.t('Disabled') :
        Drupal.t('Active');
    });
  }
};

})(jQuery);
