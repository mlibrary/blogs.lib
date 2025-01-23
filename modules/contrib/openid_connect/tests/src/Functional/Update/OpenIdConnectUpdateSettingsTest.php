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
class OpenIdConnectUpdateSettingsTest extends UpdatePathTestBase {

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
      __DIR__ . '/../../../fixtures/update/openid_connect_30001.php',
    ];
  }

  /**
   * @covers \openid_connect_30002()
   */
  public function testUpdateHookN(): void {
    $old_settings = \Drupal::config('openid_connect.settings');
    $this->assertSame(self::PRE_UPDATE_ROLE_MAPPING, $old_settings->get('role_mappings'));

    $this->runUpdates();

    // Test openid_connect_30002().
    $new_settings = \Drupal::config('openid_connect.settings');
    $this->assertSame(self::POST_UPDATE_ROLE_MAPPING, $new_settings->get('role_mappings'));
  }

}
