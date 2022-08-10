(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.linkReturnPage = {
    attach: function (context) {
      var current_page = location.pathname,
      login_page = '/user/login';

      if (login_page != current_page) {
        $(context).find("a[href='" + login_page + "']").once('login-processed-link').each(function () {
          $(this).attr('href', $(this).attr('href') + '?destination=' + current_page);
        });
      }

    }
  }
})(jQuery, Drupal);
