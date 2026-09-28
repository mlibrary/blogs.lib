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

  /**
   * Tests auto-redirect when a disabled client exists alongside an enabled one.
   *
   * Regression test: disabled clients must be filtered out before checking
   * whether exactly one client is active. Without the fix, having any disabled
   * client would cause getClient() to count more than one and skip auto-login.
   */
  public function testAutoRedirectWithDisabledClient(): void {
    // The setUp() already created one enabled client (self::OIDC_ID).
    // Add a second client that is disabled.
    $storage = \Drupal::entityTypeManager()->getStorage('openid_connect_client');
    $disabledClient = $storage->create([
      'id' => 'disabled_client',
      'label' => 'Disabled OIDC Client',
      'plugin' => 'generic',
      'status' => FALSE,
      'settings' => [
        'client_id' => $this->randomString(8),
        'client_secret' => $this->randomString(8),
      ],
    ]);
    $disabledClient->save();

    $this->toggleAutoStart(TRUE);
    // Ensure we are the anonymous user.
    $this->drupalLogout();

    // With one enabled and one disabled client, auto-login should still
    // redirect to the OAuth provider.
    $this->drupalGet('/user/login');
    $this->assertSession()
      ->addressEquals('https://example.com/oauth2/authorize');
  }

  /**
   * Tests auto-redirect on the register and password reset routes.
   *
   * Auto-login covers user.login, user.register and user.pass. Only user.login
   * is exercised above, so assert the other two routes resolve to a name the
   * subscriber recognizes as well.
   *
   * @dataProvider dataProviderForAutoRedirectPaths
   */
  public function testAutoRedirectOnOtherLoginRoutes(string $path): void {
    $this->toggleAutoStart(TRUE);
    // Ensure we are the anonymous user.
    $this->drupalLogout();

    $this->drupalGet($path);
    $this->assertSession()
      ->addressEquals('https://example.com/oauth2/authorize');
  }

  /**
   * Data provider for testAutoRedirectOnOtherLoginRoutes().
   *
   * @return array<string, array{string}>
   *   Paths that must trigger auto-login.
   */
  public static function dataProviderForAutoRedirectPaths(): array {
    return [
      'user.register' => ['/user/register'],
      'user.pass' => ['/user/password'],
    ];
  }

  /**
   * Tests that a route outside the login routes is not redirected.
   *
   * With autostart enabled and an anonymous visitor, every request passes
   * through the subscriber, so anything other than the three login routes has
   * to render normally. /user/login/openid_connect is the useful case: it is
   * reachable by anonymous users and its path starts with /user/login, so a
   * subscriber matching on the path rather than the route name would wrongly
   * hijack the module's own login page.
   */
  public function testNoAutoRedirectOnUnrelatedRoute(): void {
    $this->toggleAutoStart(TRUE);
    // Ensure we are the anonymous user.
    $this->drupalLogout();

    $this->drupalGet('/user/login/openid_connect');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/user/login/openid_connect');
  }

}
