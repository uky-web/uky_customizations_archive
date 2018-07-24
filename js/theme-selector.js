(function($, Drupal, drupalSettings) {
  Drupal.behaviors.ukd8_customizations_theme_selector = {
      // this should be considered a hack to turn a textfield into a select
      attach: function (context, settings) {
        $('.field--name-field-theme-color-scheme').css('margin', '.75rem 0');
        $('.field--name-field-theme-color-scheme .form-item').css('margin', 0);
        $('.field--name-field-theme-color-scheme input').hide();
        var $select = $('.field--name-field-theme-color-scheme select');
        $select.each(function() {
          var $me = $(this);
          var v = $me.closest('.field--name-field-theme-color-scheme').find('input').val();
          $me.val(v);
        });
        $select.on('change', function() {
          var $me = $(this);
          $me.closest('.field--name-field-theme-color-scheme').find('input').val($me.val());
        });
      }
  };
})(jQuery, Drupal, drupalSettings);