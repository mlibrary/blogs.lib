<?php

namespace Drupal\Tests\create_user_permission\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test that "create users" able users can notify users about the new account.
 *
 * @group create_user_permission
 */
class CanNotifyTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['create_user_permission'];

  /**
   * Test that we can check the "notify user" checkbox.
   */
  public function testCanNotify() {
    $create_user = $this->drupalCreateUser([
      'create users',
    ]);
    $this->drupalLogin($create_user);
    // Go to the add person page.
    $this->drupalGet('admin/people/create');
    // Check that the "notify" thing is available.
    $this->assertTrue($this->getSession()->getPage()->find('css', '#edit-notify')->isVisible());
  }

}
