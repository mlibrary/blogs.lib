<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test the login form openid_connect alterations.
 *
 * @group openid_connect
 */
class LogoutUserTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  const CLIENT_ID = 'test';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
    'externalauth',
    'user',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The logout url.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $logoutUrl;

  /**
   * The string representation of the logout url.
   *
   * @var string
   */
  protected string $logoutUrlPlain;

  /**
   * The confirmation url.
   *
   * @var \Drupal\Core\Url
   */
  protected Url $logoutConfirmUrl;

  /**
   * The test client.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface
   */
  protected $openIdConnectClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->openIdConnectClient = $this->createTestClient(self::CLIENT_ID, 'Test OIDC Client');
    $this->placeBlock('system_menu_block:account');
    $this->placeBlock('system_messages_block');
    $this->logoutUrlPlain = '/user/logout';
    $this->logoutUrl = Url::fromRoute('user.logout');
    $this->logoutConfirmUrl = Url::fromRoute('openid_connect.logout.confirm');
  }

  /**
   * Confirm CSRF token is required to log out.
   */
  public function testCsrfOnLogout(): void {
    $this->toggleEndSessionSetting(FALSE);
    $account = $this->createUser();
    $this->drupalLogin($account);

    // Test missing csrf token does not log the user out.
    // Assert that the user is shown the confirmation form instead.
    $this->drupalGet($this->logoutUrlPlain);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($this->logoutConfirmUrl);
    // Confirm the cancel link does not logout the user.
    $this->clickLink('Cancel');
    $this->assertTrue($this->drupalUserIsLoggedIn($account));

    // Test invalid csrf token does not log the user out.
    // Also confirm the confirmation form is shown instead.
    $this->drupalGet($this->logoutUrl, ['query' => ['token' => '123']]);
    $this->assertTrue($this->drupalUserIsLoggedIn($account));
    $this->assertSession()->statusCodeEquals(200);
    // Assert the confirmation form is shown.
    $this->assertSession()->addressEquals($this->logoutConfirmUrl);
    // Test the confirmation form.
    $this->submitForm([], 'Log out');
    $this->assertFalse($this->drupalUserIsLoggedIn($account));

    // Test with a valid logout link.
    $this->drupalResetSession();
    $this->drupalLogin($account);
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');
    $this->assertFalse($this->drupalUserIsLoggedIn($account));
  }

  /**
   * Test the OpenID Redirect settings with the logout confirmation forms.
   */
  public function testOpenIdRedirectOnLogout(): void {
    // Enable end session logout redirect.
    $this->toggleEndSessionSetting(TRUE);
    // Get the endpoint of the client for assertions.
    $endpoints = $this->openIdConnectClient->getPlugin()->getEndpoints();
    $account = $this->createUser();
    // Link the account to the external auth table.
    $authmap = \Drupal::service('externalauth.authmap');
    $authmap->save($account, sprintf('openid_connect.%s', self::CLIENT_ID), $this->randomMachineName());

    // Confirm a valid logout redirects to the end session endpoint.
    $this->drupalLogin($account);
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');
    $this->assertSession()->addressEquals($endpoints['end_session']);
    $this->assertFalse($this->drupalUserIsLoggedIn($account));
    $this->drupalResetSession();

    // Confirm the logout confirmation form
    // redirects to the end session endpoint.
    $this->drupalLogin($account);
    $this->drupalGet($this->logoutUrlPlain);
    $this->assertSession()->statusCodeEquals(200);
    // Assert the confirmation form is shown.
    $this->assertSession()->addressEquals($this->logoutConfirmUrl);
    // Test the confirmation form.
    $this->submitForm([], 'Log out');
    $this->assertSession()->addressEquals($endpoints['end_session']);
    $this->assertFalse($this->drupalUserIsLoggedIn($account));
    $this->drupalResetSession();

    // Confirm the regular logout redirect is working.
    $this->toggleEndSessionSetting(FALSE);
    $this->setRedirectLogoutUrl('/path/to/redirect');
    $this->drupalLogin($account);
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');
    $this->assertSession()->addressEquals('/path/to/redirect');
    $this->assertFalse($this->drupalUserIsLoggedIn($account));
    $this->drupalResetSession();

    // Confirm the end session without an endpoint goes to the redirect url.
    $client = $this->getTestClient(self::CLIENT_ID);
    $this->assertNotNull($client);
    $plugin = $client->getPlugin();
    $clientConfig = $plugin->getConfiguration();
    $clientConfig['end_session_endpoint'] = '';
    $plugin->setConfiguration($clientConfig);
    $client->save();
    $this->toggleEndSessionSetting(TRUE);
    $this->setRedirectLogoutUrl('/path/to/different/redirect');

    $this->drupalLogin($account);
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');
    $this->assertSession()->addressEquals('/path/to/different/redirect');
    $this->assertFalse($this->drupalUserIsLoggedIn($account));
    $this->drupalResetSession();

    // Confirm empty redirects go to the home page.
    $this->toggleEndSessionSetting(FALSE);
    // Set the redirect to an empty string.
    $this->setRedirectLogoutUrl('');
    $this->drupalLogin($account);
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');
    $this->assertSession()->addressEquals(Url::fromRoute('<front>'));
  }

}
