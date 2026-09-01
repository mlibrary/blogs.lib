<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Tests the password reset form validation for connected accounts.
 *
 * Accounts that authenticate through an external provider must not be able to
 * request a local password reset, unless they hold the 'openid connect set own
 * password' permission. The validator has to reach the same verdict whether the
 * visitor typed their email address or their username, so both lookups are
 * covered here.
 *
 * @group openid_connect
 */
class UserPassFormValidateTest extends BrowserTestBase {

  use AssertMailTrait;
  use OpenIdClientTestTrait;

  const CLIENT_LABEL = 'Test OIDC Client';

  const CLIENT_ID = 'test_oidc_client';

  /**
   * The externalauth authmap service.
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
    $this->createTestClient(self::CLIENT_ID, self::CLIENT_LABEL);
    $this->authmap = \Drupal::service('externalauth.authmap');
  }

  /**
   * Connect an account to the test client.
   *
   * @param \Drupal\user\UserInterface $account
   *   The account to connect.
   */
  protected function connectAccount(UserInterface $account): void {
    $this->authmap->save(
      $account,
      sprintf('openid_connect.%s', self::CLIENT_ID),
      $this->randomMachineName()
    );
  }

  /**
   * Submit the password reset form with the given identifier.
   *
   * @param string $identifier
   *   The username or email address to request a reset for.
   */
  protected function requestPasswordReset(string $identifier): void {
    $this->drupalGet('/user/password');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(['name' => $identifier], 'Submit');
  }

  /**
   * Assert the connected-account validation error was raised.
   *
   * @param string $identifier
   *   The identifier that was submitted.
   */
  protected function assertConnectedAccountError(string $identifier): void {
    $this->assertSession()->pageTextContains(
      sprintf('%s is connected to an external authentication system.', $identifier)
    );
    // Validation failed, so the form was never submitted and no reset mail was
    // generated.
    $this->assertCount(0, $this->getMails(['id' => 'user_password_reset']));
  }

  /**
   * Assert the reset request went through untouched by this module.
   *
   * @param string $identifier
   *   The identifier that was submitted.
   * @param int $expectedMailCount
   *   The number of reset mails expected to have been sent.
   */
  protected function assertResetRequestAllowed(string $identifier, int $expectedMailCount): void {
    $this->assertSession()->pageTextNotContains('is connected to an external authentication system.');
    $this->assertSession()->pageTextContains(
      sprintf('If %s is a valid account, an email will be sent with instructions to reset your password.', $identifier)
    );
    $this->assertCount($expectedMailCount, $this->getMails(['id' => 'user_password_reset']));
  }

  /**
   * A connected account cannot reset its password by email address.
   *
   * Covers the load-by-mail lookup in the validator.
   */
  public function testConnectedAccountByEmail(): void {
    $account = $this->createUser([]);
    $this->connectAccount($account);

    $this->requestPasswordReset($account->getEmail());
    $this->assertConnectedAccountError($account->getEmail());
  }

  /**
   * A connected account cannot reset its password by username.
   *
   * Covers the load-by-name fallback: the email lookup returns no rows, so the
   * validator has to fall through to the username lookup rather than treat the
   * empty result as "no such user".
   */
  public function testConnectedAccountByUsername(): void {
    $account = $this->createUser([]);
    $this->connectAccount($account);

    $this->requestPasswordReset($account->getAccountName());
    $this->assertConnectedAccountError($account->getAccountName());
  }

  /**
   * An account with no connected provider can reset its password.
   */
  public function testUnconnectedAccount(): void {
    $account = $this->createUser([]);

    $this->requestPasswordReset($account->getAccountName());
    $this->assertResetRequestAllowed($account->getAccountName(), 1);
  }

  /**
   * A connected account holding the password permission can reset by email.
   */
  public function testConnectedAccountWithPasswordPermissionByEmail(): void {
    $account = $this->createUser(['openid connect set own password']);
    $this->connectAccount($account);

    $this->requestPasswordReset($account->getEmail());
    $this->assertResetRequestAllowed($account->getEmail(), 1);
  }

  /**
   * A connected account holding the password permission can reset by username.
   */
  public function testConnectedAccountWithPasswordPermissionByUsername(): void {
    $account = $this->createUser(['openid connect set own password']);
    $this->connectAccount($account);

    $this->requestPasswordReset($account->getAccountName());
    $this->assertResetRequestAllowed($account->getAccountName(), 1);
  }

  /**
   * An identifier matching no account is left to core to handle.
   *
   * Covers the path where both lookups miss and the validator must add no
   * error, so the module does not disclose which accounts exist.
   */
  public function testUnknownIdentifier(): void {
    $identifier = $this->randomMachineName();

    $this->requestPasswordReset($identifier);
    $this->assertResetRequestAllowed($identifier, 0);
  }

}
