<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the disconnect user permission.
 *
 * @group openid_connect
 */
class UserPermissionTest extends BrowserTestBase {

  const CLIENT_LABEL = 'Test OIDC Client';

  const CLIENT_ID = 'test_oidc_label';

  /**
   * The authmap service taken from the container.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The OpenID Connect client entity storage taken from the container.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $openIdEntityStorage;

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
    $this->openIdEntityStorage = \Drupal::service('entity_type.manager')->getStorage('openid_connect_client');
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
   * Create a test client.
   *
   * @return \Drupal\openid_connect\OpenIDConnectClientEntityInterface
   *   The test client.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestClient(): OpenIDConnectClientEntityInterface {
    $client = $this->openIdEntityStorage->create(
      [
        'id' => self::CLIENT_ID,
        'label' => self::CLIENT_LABEL,
        'plugin' => 'generic',
        'redirect_uri' => 'http://localhost',
        'grant_type' => 'authorization_code',
        'response_type' => 'code',
        'authorization_endpoint' => 'http://localhost/authorize',
        'token_endpoint' => 'http://localhost/token',
        'userinfo_endpoint' => 'http://localhost/userinfo',
        'jwks_uri' => 'http://localhost/jwks',
        'scopes' => ['openid email'],
        'client_secret' => 'test',
      ]
    );
    $client->save();

    return $client;
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
    $this->createTestClient();

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
  public function dataProviderForTestDisconnectFormSubmissionsForOthers(): array {
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
    $this->createTestClient();
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
  public function dataProviderForTestDisconnectFormSubmissionsForSelf(): array {
    return [
      'administer openid connect clients permission' => ['administer openid connect clients', FALSE],
      'disconnect openid connected accounts permission' => ['disconnect openid connected accounts', TRUE],
      'manage own openid connect accounts permission' => ['manage own openid connect accounts', TRUE],
      'openid connect set own password permission' => ['openid connect set own password', FALSE],
    ];
  }

}
