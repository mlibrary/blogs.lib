<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update that adds provider_slug to existing OpenID Connect clients.
 *
 * @group Update
 * @group openid_connect
 */
class ProviderSlugUpdate30007Test extends UpdatePathTestBase {

  const CLIENT_WITH_PROVIDER_SLUG = 'client_with_provider_slug';
  const CLIENT_WITHOUT_PROVIDER_SLUG = 'client_without_provider_slug';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'file',
    'openid_connect',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      $this->root . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/openid_connect_30007.php.gz',
    ];
  }

  /**
   * Tests that provider_slug is added to clients without it.
   *
   * @covers openid_connect_update_30007
   */
  public function testProviderSlugUpdate(): void {
    // Verify the state before running updates.
    $client_with_slug = \Drupal::config('openid_connect.client.' . self::CLIENT_WITH_PROVIDER_SLUG);
    $this->assertEquals('custom_slug', $client_with_slug->get('settings.provider_slug'));

    $client_without_slug = \Drupal::config('openid_connect.client.' . self::CLIENT_WITHOUT_PROVIDER_SLUG);
    $settings = $client_without_slug->get('settings');
    $this->assertArrayNotHasKey('provider_slug', $settings);

    $this->runUpdates();

    // Verify the state after running updates.
    $client_with_slug = \Drupal::config('openid_connect.client.' . self::CLIENT_WITH_PROVIDER_SLUG);
    $this->assertEquals('custom_slug', $client_with_slug->get('settings.provider_slug'));

    $client_without_slug = \Drupal::config('openid_connect.client.' . self::CLIENT_WITHOUT_PROVIDER_SLUG);
    $settings = $client_without_slug->get('settings');
    $this->assertEquals('', $settings['provider_slug']);
  }

}
