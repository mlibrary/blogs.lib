<?php

namespace Drupal\Tests\create_user_permission\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Test that newly created users can be created, unblocked.
 *
 * @group create_user_permission
 */
class UserNotBlockedTest extends BrowserTestBase {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['create_user_permission'];

  /**
   * Test that users created are not blocked.
   */
  public function testNotBlocked() {
    $values = [
      'visitors_admin_approval',
      'admin_only',
    ];
    foreach ($values as $value) {
      \Drupal::configFactory()
        ->getEditable('user.settings')
        ->set('register', $value)
        ->save();
      $create_user = $this->drupalCreateUser([
        'create users',
      ]);
      $this->drupalLogin($create_user);
      // Go to the add person page.
      $this->drupalGet('admin/people/create');
      $testmail = 'testuser1@example.com';
      $password = 'testpassword';
      $this->drupalGet('admin/people/create');
      $this->submitForm([
        'mail' => $testmail,
        'name' => $testmail,
        'pass[pass1]' => $password,
        'pass[pass2]' => $password,
      ], 'Create new account');
      /** @var \Drupal\user\Entity\User $user */
      $user = user_load_by_mail($testmail);
      $this->assertFalse($user->isBlocked());
    }
  }

}
