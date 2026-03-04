<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updates to openid_connect settings configuration.
 *
 * @group Update
 * @group openid_connect
 */
class OpenIdConnectUpdateSettings30003Test extends UpdatePathTestBase {

  const PRE_UPDATE_ROLE_MAPPING = [
    'content_editor' => [
      'test_group',
    ],
    'administrator' => [],
  ];

  const POST_UPDATE_ROLE_MAPPING = [
    'content_editor' => [
      'test_group',
    ],
  ];

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
  ];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      $this->root . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/openid_connect_30003.php.gz',
    ];
  }

  /**
   * @covers openid_connect_update_30003
   */
  public function testUpdateHook30003(): void {
    $old_generic_client_good = \Drupal::config('openid_connect.client.generic_client_good');
    $this->assertSame([], $old_generic_client_good->get('settings.scopes'));
    $old_generic_client_bad = \Drupal::config('openid_connect.client.generic_client_bad');
    $this->assertSame('', $old_generic_client_bad->get('settings.scopes'));
    $old_okta_client_good = \Drupal::config('openid_connect.client.okta_client_good');
    $this->assertSame([], $old_okta_client_good->get('settings.scopes'));
    $old_okta_client_bad = \Drupal::config('openid_connect.client.okta_client_bad');
    $this->assertSame('', $old_okta_client_bad->get('settings.scopes'));

    $this->runUpdates();

    // Test openid_connect_30003().
    $new_generic_client_good = \Drupal::config('openid_connect.client.generic_client_good');
    $this->assertSame([], $new_generic_client_good->get('settings.scopes'));
    $new_generic_client_bad = \Drupal::config('openid_connect.client.generic_client_bad');
    $this->assertSame([], $new_generic_client_bad->get('settings.scopes'));
    $new_okta_client_good = \Drupal::config('openid_connect.client.okta_client_good');
    $this->assertSame([], $new_okta_client_good->get('settings.scopes'));
    $new_okta_client_bad = \Drupal::config('openid_connect.client.okta_client_bad');
    $this->assertSame([], $new_okta_client_bad->get('settings.scopes'));
  }

}
