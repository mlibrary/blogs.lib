<?php
/**
 * @file
 * Describe hooks provided by the Passwordless module.
 */

/**
 * Allows other modules to customize the landing page after a successful login.
 *
 * @param string $route_name
 * @param array $route_parameters
 */
function hook_passwordless_login_redirect_alter(string &$route_name, array &$route_parameters = []) {
  $route_name = '<front>';
}
