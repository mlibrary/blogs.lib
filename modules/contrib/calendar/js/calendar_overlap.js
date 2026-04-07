/**
 * @file
 * Create the splitter, set the viewport size, and set the position of the scrollbar to the first item.
 */

(function ($) {
  Drupal.behaviors.calendarSetScroll = {
    attach(context) {
      document.getElementById('single-day-container').style.visibility =
        'hidden';

      // Make multi-day resizable - adapted from textarea.js.
      $(
        '.header-body-divider:not(.header-body-divider-processed)',
        context,
      ).each(function () {
        const divider = $(this).addClass('header-body-divider-processed');
        let startY = divider.offset().top;

        function performDrag(e) {
          const offset = e.pageY - startY;
          const mwc = $('#multi-day-container');
          const sdc = $('#single-day-container');
          const mwcHeight = mwc.height();
          const sdcHeight = sdc.height();
          const maxHeight = mwcHeight + sdcHeight;

          mwc.height(Math.min(maxHeight, Math.max(0, mwcHeight + offset)));
          sdc.height(Math.min(maxHeight, Math.max(0, sdcHeight - offset)));
          startY = divider.offset().top;
          return false;
        }

        function endDrag() {
          $(document).off('mousemove', performDrag).off('mouseup', endDrag);
        }

        function startDrag(e) {
          startY = divider.offset().top;
          $(document).on('mousemove', performDrag).on('mouseup', endDrag);
          return false;
        }

        // Add the grippie icon.
        divider
          .prepend('<div class="grippie"></div>')
          .on('mousedown', startDrag);
      });

      $('.single-day-footer:not(.single-day-footer-processed)', context).each(
        function () {
          const divider = $(this).addClass('single-day-footer-processed');
          let startY = divider.offset().top;

          function performDrag(e) {
            const offset = e.pageY - startY;
            const sdc = $('#single-day-container');
            sdc.height(Math.max(0, sdc.height() + offset));
            startY = divider.offset().top;
            return false;
          }

          function endDrag() {
            $(document).off('mousemove', performDrag).off('mouseup', endDrag);
          }

          function startDrag(e) {
            startY = divider.offset().top;
            $(document).on('mousemove', performDrag).on('mouseup', endDrag);
            return false;
          }

          // Add the grippie icon.
          divider
            .prepend('<div class="grippie"></div>')
            .on('mousedown', startDrag);
        },
      );

      // Scroll the viewport to the first item.
      function calendarScrollToFirst() {
        const firstItem = $('div.first_item');
        if (firstItem.length > 0) {
          const y =
            firstItem.offset().top - $('#single-day-container').offset().top;
          $('#single-day-container').scrollTop(y);
        }
      }

      // Size the single-day view.
      function calendarResizeViewport() {
        // Size of the browser window.
        const viewportHeight = window.innerHeight || $(window).height();
        const top = $('#single-day-container').offset().top;

        // Give it a 20-pixel margin at the bottom.
        $('#single-day-container').height(viewportHeight - top - 20);
      }

      calendarResizeViewport();
      calendarScrollToFirst();

      document.getElementById('single-day-container').style.visibility =
        'visible';
    },
  };
})(jQuery);
