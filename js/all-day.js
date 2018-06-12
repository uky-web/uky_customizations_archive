(function($, Drupal, drupalSettings) {
  Drupal.behaviors.ukd8_customizations_all_day = {
      attach: function (context, settings) {
          var updateTime = function($allday) {
              var $parent = $allday.closest('form');
              if ($allday.is(':checked')) {
                  $parent.find('input[type="time"]').each(function() {
                      var $input = $(this);
                      var val = '00:00:00';
                      if ($input.attr('name').indexOf('end_value')>=0) {
                          val = '23:59:59';
                      }
                      $input
                      .val(val)
                      .closest('.form-item').hide();
                  });
              } else {
                  $parent.find('input[type="time"]').closest('.form-item').show();
              }
          };

          var $allday = $('input[name="field_all_day[value]"]');
          $allday.on('change', function(e) {
              updateTime($(this));
          });
          if (drupalSettings.ukd8_customizations.all_day.all_day) {
              $allday.attr('checked', 'checked');
          }
          updateTime($allday);
      }
  };
})(jQuery, Drupal, drupalSettings);