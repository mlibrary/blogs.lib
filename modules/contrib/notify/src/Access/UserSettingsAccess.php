<?php

namespace Drupal\notify\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class UserSettingsAccess.
 *
 * To check permission to access UserSettings form.
 *
 * @package Drupal\notify\Access
 */
class UserSettingsAccess {

  /**
   * Checks access for the UserSettings form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $current_user = $route_match->getParameter('user');
    if ($current_user === $account->id()) {
      return AccessResult::allowedIf($account->hasPermission('access notify'));
    }
    return AccessResult::allowedIf($account->hasPermission('administer notify'));
  }

}
