<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests that saveUserinfo() handles the 'mail' property without a fatal error.
 *
 * Regression test for the bug where loadByProperties() returns an array but
 * the code called ->id() directly on it instead of on the first element.
 *
 * The bug is triggered when:
 * - 'mail' is mapped in userinfo_mappings, AND
 * - hook_openid_connect_user_properties_ignore_alter() removes 'mail' from the
 *   ignore list so that saveUserinfo() actually processes the email claim.
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnect
 * @group openid_connect
 */
class SaveUserinfoMailTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'file',
    'openid_connect',
    'openid_connect_test_hooks',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('externalauth', ['authmap']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user', 'openid_connect']);

    // Map the 'email' claim to the 'mail' user property.
    $this->config('openid_connect.settings')
      ->set('userinfo_mappings', ['mail' => 'email'])
      ->save();

    // Activate the test hook that removes 'mail' from the ignore list, so
    // saveUserinfo() will reach the email-handling code path.
    $this->container->get('state')->set('openid_connect_test_hooks.unignore_mail', TRUE);
  }

  /**
   * Data provider for testSaveUserinfoMailHandling().
   *
   * Each case describes:
   * - whether a second "other" account pre-owns the claim email
   * - the user's starting email
   * - the email claim value supplied to saveUserinfo()
   * - the email address expected on the account afterwards.
   *
   * @return array<string, array{bool, string, string, string}>
   *   Cases for testing saveUserinfo() email handling.
   */
  public static function saveUserinfoMailProvider(): array {
    return [
      // The claim matches the user's own current address. Before the fix this
      // produced a fatal "Call to a member function id() on array" because
      // loadByProperties() returns an array and the code called ->id() on it
      // directly.
      'same email as own account' => [FALSE, 'user@example.com', 'user@example.com', 'user@example.com'],

      // The claim is a fresh address; it should be applied.
      'new available email' => [FALSE, 'old@example.com', 'new@example.com', 'new@example.com'],

      // The claim is already owned by a different account; the address must
      // not change.
      'email taken by another account' => [TRUE, 'original@example.com', 'taken@example.com', 'original@example.com'],
    ];
  }

  /**
   * Tests email claim handling in saveUserinfo().
   *
   * @dataProvider saveUserinfoMailProvider
   */
  public function testSaveUserinfoMailHandling(
    bool $createOtherAccountWithClaimEmail,
    string $startingEmail,
    string $claimEmail,
    string $expectedEmail,
  ): void {
    if ($createOtherAccountWithClaimEmail) {
      User::create([
        'name' => 'other',
        'mail' => $claimEmail,
        'status' => 1,
      ])->save();
    }

    $account = User::create([
      'name' => 'testuser',
      'mail' => $startingEmail,
      'status' => 1,
    ]);
    $account->save();

    // This must not throw a fatal error regardless of
    // whether loadByProperties() returns an empty or non-empty array.
    $result = $this->container->get('openid_connect.openid_connect')->saveUserinfo($account, [
      'tokens' => [],
      'user_data' => [],
      'userinfo' => ['email' => $claimEmail],
      'plugin_id' => 'generic',
      'sub' => 'test_subject_123',
    ]);

    $this->assertTrue($result);
    $this->assertEquals($expectedEmail, $account->getEmail());
  }

}
