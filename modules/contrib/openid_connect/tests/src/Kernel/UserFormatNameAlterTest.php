<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Test the hook_user_format_alter() implementation.
 *
 * @group openid_connect
 */
class UserFormatNameAlterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'file',
    'openid_connect',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user']);
  }

  /**
   * Tests getDisplayName() for an unsaved user.
   */
  public function testUnsavedUserDisplayName(): void {
    // Create some existing users and set their oidc_name in user.data.
    $user1 = User::create([
      'name' => 'user1',
      'mail' => 'user1@example.com',
      'status' => 1,
    ]);
    $user1->save();
    $this->container->get('user.data')->set('openid_connect', $user1->id(), 'oidc_name', 'OIDC Name 1');

    $user2 = User::create([
      'name' => 'user2',
      'mail' => 'user2@example.com',
      'status' => 1,
    ]);
    $user2->save();
    $this->container->get('user.data')->set('openid_connect', $user2->id(), 'oidc_name', 'OIDC Name 2');

    // Create a new, unsaved user.
    $unsaved_user = User::create([
      'name' => 'unsaved_user@example.com',
      'status' => 1,
    ]);

    // Call getDisplayName().
    // If the bug exists, this will return an array of all oidc_names.
    $display_name = $unsaved_user->getDisplayName();

    $this->assertIsString($display_name, 'Display name should be a string.');
    $this->assertEquals('unsaved_user@example.com', $display_name);

    $anonymous = User::getAnonymousUser();
    $display_name = $anonymous->getDisplayName();
    $this->assertIsString($display_name, 'Display name should be a string.');
  }

  /**
   * Tests that hook_user_format_name_alter() applies the OIDC name correctly.
   *
   * The hook substitutes the OIDC name only when the stored username looks like
   * an email address (contains '@') and is not an auto-generated 'oidc_' name.
   */
  public function testFormatNameAlter(): void {
    $userData = $this->container->get('user.data');

    // User whose name is an email address: OIDC name should be substituted.
    $emailUser = User::create([
      'name' => 'jane@example.com',
      'mail' => 'jane@example.com',
      'status' => 1,
    ]);
    $emailUser->save();
    $userData->set('openid_connect', $emailUser->id(), 'oidc_name', 'Jane Doe');
    $this->assertEquals('Jane Doe', $emailUser->getDisplayName());

    // User whose name starts with 'oidc_': OIDC name should NOT be
    // substituted even though an oidc_name value is stored.
    $oidcPrefixedUser = User::create([
      'name' => 'oidc_google_abc123',
      'mail' => 'oidc@example.com',
      'status' => 1,
    ]);
    $oidcPrefixedUser->save();
    $userData->set('openid_connect', $oidcPrefixedUser->id(), 'oidc_name', 'Should Not Appear');
    $this->assertEquals('oidc_google_abc123', $oidcPrefixedUser->getDisplayName());

    // User with a plain username (no '@'): OIDC name should NOT be
    // substituted.
    $plainUser = User::create([
      'name' => 'johndoe',
      'mail' => 'john@example.com',
      'status' => 1,
    ]);
    $plainUser->save();
    $userData->set('openid_connect', $plainUser->id(), 'oidc_name', 'Should Not Appear Either');
    $this->assertEquals('johndoe', $plainUser->getDisplayName());
  }

}
