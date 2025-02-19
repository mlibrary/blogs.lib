<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the disconnect user permission.
 *
 * @group openid_connect
 */
class UserPermissionTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  const CLIENT_LABEL = 'Test OIDC Client';

  const CLIENT_ID = 'test_oidc_label';

  /**
   * The authmap service taken from the container.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
    'externalauth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->authmap = \Drupal::service('externalauth.authmap');
  }

  /**
   * Confirm a user cannot access another user's connected accounts page.
   */
  public function testDisconnectPermissionDenied(): void {
    $account = $this->createUser([]);
    $target = $this->createUser([]);

    // Login and confirm the account can access the target
    // user's connected accounts page.
    $this->drupalLogin($account);
    $this->drupalGet("/user/{$target->id()}/connected-accounts");
    $this->assertSession()
      ->statusCodeEquals(403);
  }

  /**
   * Test the disconnect connected accounts for others form submission.
   *
   * @dataProvider dataProviderForTestDisconnectFormSubmissionsForOthers
   */
  public function testDisconnectFormSubmissionsForOthers(
    string $permission,
    bool $expected,
  ): void {
    $subjectId = $this->randomMachineName();

    // Create a client.
    $this->createTestClient(self::CLIENT_ID, self::CLIENT_LABEL);

    $account = $this->createUser([$permission]);
    $target = $this->createUser([]);

    // Add external auth for the target user.
    $this->authmap->save($target, sprintf('openid_connect.%s', self::CLIENT_ID), $subjectId);

    // Login and confirm the account can access the
    // target user's connected accounts page.
    $this->drupalLogin($account);
    $this->drupalGet("/user/{$target->id()}/connected-accounts");
    $this->assertSession()
      ->statusCodeEquals($expected ? 200 : 403);

    if ($expected) {
      $this->assertSession()->buttonExists(sprintf('Disconnect from %s', self::CLIENT_LABEL));
      $this->submitForm([], sprintf('Disconnect from %s', self::CLIENT_LABEL));

      $this->assertSession()
        ->pageTextContains(sprintf('Account successfully disconnected from %s', self::CLIENT_LABEL));
      // Confirm the authmap entry has been removed.
      $this->assertEmpty($this->authmap->get((int) $target->id(), sprintf('openid_connect.%s', self::CLIENT_ID)));
    }
    else {
      $this->assertNotEmpty(
        $this->authmap->get((int) $target->id(),
        sprintf('openid_connect.%s', self::CLIENT_ID))
      );
    }
  }

  /**
   * Data provider for the testDisconnectFormSubmissionsForOthers test.
   *
   * @return array[]
   *   Parameters for the testDisconnectFormSubmissionsForOthers test.
   */
  public static function dataProviderForTestDisconnectFormSubmissionsForOthers(): array {
    return [
      'administer openid connect clients permission' => ['administer openid connect clients', FALSE],
      'disconnect openid connected accounts permission' => ['disconnect openid connected accounts', TRUE],
      'manage own openid connect accounts permission' => ['manage own openid connect accounts', FALSE],
      'openid connect set own password permission' => ['openid connect set own password', FALSE],
    ];
  }

  /**
   * Test the disconnect connected accounts for self form submission.
   *
   * @dataProvider dataProviderForTestDisconnectFormSubmissionsForSelf
   */
  public function testDisconnectFormSubmissionsForSelf(
    string $permission,
    bool $expected,
  ): void {
    $subjectId = $this->randomMachineName();

    // Create a client.
    $this->createTestClient(self::CLIENT_ID, self::CLIENT_LABEL);
    // Create a user.
    $target = $this->createUser([$permission]);

    // Add external auth for the target user.
    $this->authmap->save($target, sprintf('openid_connect.%s', self::CLIENT_ID), $subjectId);

    // Login and confirm the account can access the
    // target user's connected accounts page.
    $this->drupalLogin($target);
    $this->drupalGet("/user/{$target->id()}/connected-accounts");
    $this->assertSession()
      ->statusCodeEquals($expected ? 200 : 403);

    if ($expected) {
      $this->assertSession()->buttonExists(sprintf('Disconnect from %s', self::CLIENT_LABEL));
      $this->submitForm([], sprintf('Disconnect from %s', self::CLIENT_LABEL));

      $this->assertSession()
        ->pageTextContains(sprintf('Account successfully disconnected from %s', self::CLIENT_LABEL));
      // Confirm the authmap entry has been removed.
      $this->assertEmpty($this->authmap->get((int) $target->id(), sprintf('openid_connect.%s', self::CLIENT_ID)));
    }
    else {
      $this->assertNotEmpty(
        $this->authmap->get((int) $target->id(),
        sprintf('openid_connect.%s', self::CLIENT_ID))
      );
    }
  }

  /**
   * Data provider for the testDisconnectFormSubmissionsForSelf test.
   *
   * @return array[]
   *   Parameters for the testDisconnectFormSubmissionsForOthers test.
   */
  public static function dataProviderForTestDisconnectFormSubmissionsForSelf(): array {
    return [
      'administer openid connect clients permission' => ['administer openid connect clients', FALSE],
      'disconnect openid connected accounts permission' => ['disconnect openid connected accounts', TRUE],
      'manage own openid connect accounts permission' => ['manage own openid connect accounts', TRUE],
      'openid connect set own password permission' => ['openid connect set own password', FALSE],
    ];
  }

}
