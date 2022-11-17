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

var role = document.getElementById("view-roles-target-id-table-column");
if (role) {
  const params = new Proxy(new URLSearchParams(window.location.search), {
    get: (searchParams, prop) => searchParams.get(prop),
  });
  let order = params.order;
  let sort = params.sort;
  if (order == 'roles_target_id' && sort == 'asc') {
    role.innerHTML = '<a href="?order=roles_target_id&sort=desc">Roles</a>';
  }
  else {
    role.innerHTML = '<a href="?order=roles_target_id&sort=asc">Roles</a>';
  }
}
