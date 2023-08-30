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

}
