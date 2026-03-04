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
class IssAllowedSchemaUpdate30004Test extends UpdatePathTestBase {

  const HAS_ALLOWED_DOMAINS = 'has_allowed_domains';
  const MISSING_ALLOWED_DOMAINS = 'missing_allowed_domains';

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
      __DIR__ . '/../../../fixtures/update/openid_connect_30004.php.gz',
    ];
  }

  /**
   * @covers openid_connect_update_30003
   */
  public function testUpdateHook30003(): void {
    $definitions = \Drupal::service('plugin.manager.openid_connect_client')->getDefinitions();
    $oidcStorage = \Drupal::entityTypeManager()
      ->getStorage('openid_connect_client');

    foreach ($definitions as $plugin) {
      /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface[] $clients */
      $clients = $oidcStorage
        ->loadByProperties(['plugin' => $plugin['id']]);

      foreach ($clients as $client) {
        match($client->id()) {
          self::HAS_ALLOWED_DOMAINS => $this->assertEquals('example.com', $client->getPlugin()->getConfiguration()['iss_allowed_domains']),
          self::MISSING_ALLOWED_DOMAINS => $this->assertArrayNotHasKey('iss_allowed_domains', $client->getPlugin()->getConfiguration()),
        };
      }
    }

    $this->runUpdates();

    foreach ($definitions as $plugin) {
      /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface[] $clients */
      $updatedClients = $oidcStorage
        ->loadByProperties(['plugin' => $plugin['id']]);

      foreach ($updatedClients as $client) {
        match ($client->id()) {
          self::HAS_ALLOWED_DOMAINS => $this->assertEquals('example.com', $client->getPlugin()->getConfiguration()['iss_allowed_domains']),
          self::MISSING_ALLOWED_DOMAINS => $this->assertEquals('', $client->getPlugin()->getConfiguration()['iss_allowed_domains']),
        };
      }
    }
  }

}
