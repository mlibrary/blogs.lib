<?php

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional test for openid connect clients.
 *
 * @todo Improve these.
 *
 * @group openid_connect
 */
class OpenIdConnectUiTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
    'externalauth',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the client list.
   */
  public function testClientList() {
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no openid connect client entities yet.');
  }

  /**
   * Test route permissions on the client enable/disable routes.
   */
  public function testEnableDisableClient(): void {
    $this->createTestClient('test_oidc_label', 'Test OIDC Client');
    // Confirm route permissions.
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/enable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/disable');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Confirm CSRF protection.
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/enable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/disable');
    $this->assertSession()->statusCodeEquals(403);

    // Confirm the UI works through the admin form.
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Disable');
    $this->clickLink('Disable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Enable');
    $this->clickLink('Enable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Disable');
  }

}
