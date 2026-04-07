/**
 * @file
 */

(function ($) {
  /*
   * Bind the colorpicker event to the form element
   */
  Drupal.behaviors.calendarColorpicker = {
    attach(context) {
      $('.edit-calendar-colorpicker', context).on('focus', function () {
        const editField = this;
        const picker = $(this)
          .closest('div')
          .parent()
          .find('.calendar-colorpicker');

        // Hide all color pickers except this one.
        $('.calendar-colorpicker').hide();
        $(picker).show();
        $.farbtastic(picker, function (color) {
          editField.value = color;
        }).setColor(editField.value);
      });
    },
  };
})(jQuery);
