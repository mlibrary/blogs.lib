<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the OpenID Connect settings form.
 *
 * @group openid_connect
 */
class AdminSettingsFormTest extends BrowserTestBase {

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
   * Tests the OpenID Connect settings form.
   */
  public function testSettingsForm(): void {
    // Assert the route is protected.
    $this->drupalGet('/admin/config/people/openid-connect/settings');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Confirm the client overview is accessible.
    $this->drupalGet('/admin/config/people/openid-connect/settings');
    $this->assertSession()->statusCodeEquals(200);
  }

}
