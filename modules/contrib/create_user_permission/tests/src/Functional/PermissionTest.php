<?php

namespace Drupal\Tests\create_user_permission\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the JavaScript functionality of the Create user permission module.
 *
 * @group create_user_permission
 */
class PermissionTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'create_user_permission',
  ];

  /**
   * Tests password strength widget.
   */
  public function testPermission() {
    $create_user = $this->drupalCreateUser([
      'create users',
    ]);
    $admin_user = $this->drupalCreateUser([
      'administer users',
    ]);

    // Log in the one with the "old" permission.
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/people/create');
    // Should now require another permission.
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalLogout();

    // Then log in the one that has the permission.
    $this->drupalLogin($create_user);
    $this->drupalGet('admin/people/create');
    // Should have access.
    $this->assertSession()->statusCodeEquals(200);
  }

}
