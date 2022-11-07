(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.linkReturnPage = {
    attach: function (context) {
      var current_page = location.pathname,
      login_page = '/user/login',
      signup_page = '/blogs-signup';

      if (login_page != current_page) {
        if (signup_page == current_page) {
          current_page = location.search.replace("?destination=", "");
        }
        $(context).find("a[href='" + login_page + "']").once('login-processed-link').each(function () {
          $(this).attr('href', $(this).attr('href') + '?destination=' + current_page);
        });
      }

    }
  }
})(jQuery, Drupal);
