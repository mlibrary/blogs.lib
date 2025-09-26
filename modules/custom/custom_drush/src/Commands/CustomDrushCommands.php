<?php

namespace Drupal\custom_drush\Commands;

use Drush\Commands\DrushCommands;
use Drupal\Core\DrupalKernel;
use Drupal\views\Views;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class CustomDrushCommands extends DrushCommands {

  /**
   * Show user data from mcomm.
   *
   * @param $name
   *   The name for saying hello
   * @validate-module-enabled custom_drush, custom_blogs
   *
   * @command custom_drush:user__data_from_mcomm_data
   * @aliases user-data-from-mcomm-data
   */
  public function userDataFromMcomm($name) {
    $user_data = _get_mcommunity_user($name);
    print_r($user_data);
  }

  /**
   * Show get all membership.
   *
   * @validate-module-enabled custom_drush, custom_blogs_og
   *
   * @command custom_drush:verify_all_memberships
   * @aliases verify-all-memberships
   */
  public function verifyAllMemberships() {
    $memberships = _custom_blogs_og_verify_all_users();
    _custom_drush_send_membership_mail($memberships);
  }

  /**
   * Show get memberships from nid.
   *
   * @param $nid
   *   The nid for saying hello
   * @validate-module-enabled custom_drush, custom_blogs_og
   *
   * @command custom_drush:verify_memberships_from_nid
   * @aliases verify-memberships-from-nid
   */
  public function verifyMembershipsFromNid($nid) {
    $memberships[$nid] = _custom_blogs_og_verify_users($nid);
    _custom_drush_send_membership_mail($memberships);
  }
}
