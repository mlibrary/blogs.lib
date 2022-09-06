<?php

namespace Drupal\Tests\create_user_permission\Functional;

use Drupal\Tests\user\Functional\UserRegistrationTest;

/**
 * Tests registration of user under different configurations.
 *
 * This runs all the core registration tests, only with this module enabled.
 *
 * @group create_user_permission
 */
class RegistrationTest extends UserRegistrationTest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['field_test', 'create_user_permission'];

}
