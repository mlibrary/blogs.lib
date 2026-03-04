<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

/**
 * Tests the auto login process.
 *
 * @group openid_connect
 */
class AutoLoginTest extends OpenIdConnectTestBase {

  const OIDC_LABEL = 'Label For OIDC Client';

  const OIDC_ID = 'oidc_client';

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
    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/people/openid-connect/add/generic');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(
      [
        'label' => self::OIDC_LABEL,
        'id' => self::OIDC_ID,
        'settings[client_id]' => $this->randomString(8),
        'settings[client_secret]' => $this->randomString(8),
      ],
      'Create OpenID Connect client'
    );

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/admin/config/people/openid-connect');
    $this->assertSession()->pageTextContains("OpenID Connect client Label For OIDC Client has been added.");
  }

  /**
   * Toggle the auto start value.
   *
   * @param bool $state
   *   The state of the auto start value.
   */
  protected function toggleAutoStart(bool $state = FALSE): void {
    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/people/openid-connect/settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm(
      [
        'autostart_login' => (int) $state,
        'user_login_display' => 'below',
      ],
      'Save configuration'
    );
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the client list.
   */
  public function testNoAutoRedirect(): void {
    $this->toggleAutoStart(FALSE);
    // Ensure we are the anonymous user.
    $this->drupalLogout();
    $this->drupalGet('/user/login');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Log in');
  }

  /**
   * Tests the client list.
   */
  public function testAutoRedirect(): void {
    $this->toggleAutoStart(TRUE);
    // Ensure we are the anonymous user.
    $this->drupalLogout();

    $this->drupalGet('/user/login');
    $this->assertSession()
      ->addressEquals('https://example.com/oauth2/authorize');
  }

}
