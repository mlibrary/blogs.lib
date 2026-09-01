<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updating a site that is still on the 1.x configuration.
 *
 * A 1.x site does not have externalauth installed, so the container is built
 * with a dangling reference to externalauth.authmap right up until
 * openid_connect_update_8203() installs the module. Booting update.php at all
 * is therefore the first thing this test proves.
 *
 * @group Update
 * @group openid_connect
 * @see \Drupal\openid_connect\OpenidConnectServiceProvider
 * @see https://www.drupal.org/project/openid_connect/issues/3537149
 */
class OpenIdConnectUpdateFrom1xTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/openid_connect_8107.php.gz',
      __DIR__ . '/../../../fixtures/update/openid_connect_8107_dash.php.gz',
    ];
  }

  /**
   * The fixture really is a 1.x site.
   */
  protected function assertPreUpdateState(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('externalauth'));
    $this->assertSame(8107, \Drupal::service('update.update_hook_registry')->getInstalledVersion('openid_connect'));

    $database = \Drupal::database();
    $this->assertTrue($database->schema()->tableExists('openid_connect_authmap'));
    $this->assertFalse($database->schema()->tableExists('authmap'));

    // Client settings still live in a plain configuration object.
    $this->assertSame('test_client_id', \Drupal::config('openid_connect.settings.generic')->get('settings.client_id'));
    $this->assertTrue(\Drupal::config('openid_connect.client.generic')->isNew());

    // A second 1.x client uses a dash-bearing plugin id, which cannot become a
    // valid config entity machine name.
    $this->assertSame('dash_client_id', \Drupal::config('openid_connect.settings.acm-idm')->get('settings.client_id'));
    $this->assertTrue(\Drupal::config('openid_connect.client.acm_idm')->isNew());

    // The dash-bearing client has an identity mapping of its own, recorded
    // under the unsanitized 1.x client name.
    $this->assertSame('acm-idm', $database->select('openid_connect_authmap', 'a')
      ->fields('a', ['client_name'])
      ->condition('sub', 'sub-of-dash-user')
      ->execute()
      ->fetchField());
  }

  /**
   * Updates run to completion and carry the 1.x state across.
   */
  public function testUpdateFrom1x(): void {
    $this->assertPreUpdateState();

    // Fails on any update hook that errors out.
    $this->runUpdates();

    // openid_connect_update_8198() / _8203().
    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('externalauth'));

    // openid_connect_update_8204() / _8205(): the identity mapping moved to
    // the externalauth table and the old one is gone.
    $database = \Drupal::database();
    $this->assertFalse($database->schema()->tableExists('openid_connect_authmap'));
    $authmap = $database->select('authmap', 'a')
      ->fields('a', ['uid', 'provider', 'authname'])
      ->condition('provider', 'openid_connect.generic')
      ->execute()
      ->fetchAssoc();
    $this->assertSame(
      ['uid' => '1', 'provider' => 'openid_connect.generic', 'authname' => 'sub-of-user-1'],
      $authmap,
    );

    // The mapping of the dash-bearing client moved along with it, under the
    // machine name that update 8200 sanitized the plugin id into. Anything
    // else and OpenIDConnect would no longer find the account, because it
    // looks the provider up as 'openid_connect.' . $client->id().
    $dashAuthmap = $database->select('authmap', 'a')
      ->fields('a', ['uid', 'provider', 'authname'])
      ->condition('authname', 'sub-of-dash-user')
      ->execute()
      ->fetchAssoc();
    $this->assertSame(
      ['uid' => '1', 'provider' => 'openid_connect.acm_idm', 'authname' => 'sub-of-dash-user'],
      $dashAuthmap,
    );
    $this->assertSame('openid_connect.' . \Drupal::config('openid_connect.client.acm_idm')->get('id'), $dashAuthmap['provider']);

    // The unsanitized provider name is gone, so no mapping is left behind.
    $this->assertSame('0', (string) $database->select('authmap', 'a')
      ->condition('provider', 'openid_connect.acm-idm')
      ->countQuery()
      ->execute()
      ->fetchField());

    // openid_connect_update_8199() / _8200(): the settings object became a
    // config entity, keeping the credentials and endpoints.
    $this->assertTrue(\Drupal::config('openid_connect.settings.generic')->isNew());
    $client = \Drupal::config('openid_connect.client.generic');
    $this->assertSame('generic', $client->get('plugin'));
    $this->assertTrue($client->get('status'));
    $settings = $client->get('settings');
    $this->assertSame('test_client_id', $settings['client_id']);
    $this->assertSame('test_client_secret', $settings['client_secret']);
    $this->assertSame('https://example.com/oauth2/authorize', $settings['authorization_endpoint']);
    $this->assertSame('https://example.com/oauth2/token', $settings['token_endpoint']);
    $this->assertSame('https://example.com/oauth2/UserInfo', $settings['userinfo_endpoint']);

    // Settings added by the later update hooks.
    $this->assertSame(['openid', 'email'], $settings['scopes']);
    $this->assertSame(['login'], $settings['prompt']);
    $this->assertSame('', $settings['iss_allowed_domains']);
    $this->assertSame('', $settings['issuer_url']);
    $this->assertSame('', $settings['end_session_endpoint']);

    // openid_connect_update_8200() preserves the original plugin id as the
    // provider_slug. The config entity machine name is unchanged for the
    // dash-free generic plugin.
    $this->assertSame('generic', $settings['provider_slug']);
    $this->assertSame('generic', $client->get('id'));

    // The dash-bearing plugin id becomes a valid entity machine name with the
    // dash sanitized, while the original id is kept as the provider_slug.
    $this->assertTrue(\Drupal::config('openid_connect.settings.acm-idm')->isNew());
    $dashClient = \Drupal::config('openid_connect.client.acm_idm');
    $this->assertSame('acm-idm', $dashClient->get('plugin'));
    $this->assertTrue($dashClient->get('status'));
    $dashSettings = $dashClient->get('settings');
    $this->assertSame('dash_client_id', $dashSettings['client_id']);
    $this->assertSame('acm-idm', $dashSettings['provider_slug']);

    $module_settings = \Drupal::config('openid_connect.settings');
    $this->assertSame('user', $module_settings->get('redirect_login'));
    $this->assertSame('', $module_settings->get('redirect_logout'));
    $this->assertTrue($module_settings->get('end_session_enabled'));

    // The upgrade is the one moment OpenidConnectServiceProvider is doing its
    // job, so it must not be nagging the site owner about being deprecated.
    $deprecations = $database->select('watchdog', 'w')
      ->fields('w', ['message', 'variables'])
      ->condition('type', 'php')
      ->execute()
      ->fetchAll();
    foreach ($deprecations as $record) {
      $this->assertStringNotContainsString('OpenidConnectServiceProvider', $record->message . $record->variables);
    }
  }

}
