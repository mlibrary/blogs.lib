<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\openid_connect\OpenIDConnectClientEntityInterface;

/**
 * Provides a trait for creating test clients.
 */
trait OpenIdClientTestTrait {

  /**
   * Create a test client.
   *
   * @return \Drupal\openid_connect\OpenIDConnectClientEntityInterface
   *   The test client.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createTestClient(
    string $clientId,
    string $clientLabel,
  ): OpenIDConnectClientEntityInterface {
    $storage = \Drupal::service('entity_type.manager')
      ->getStorage('openid_connect_client');

    $client = $storage->create(
      [
        'id' => $clientId,
        'label' => $clientLabel,
        'plugin' => 'generic',
        'status' => TRUE,
        'settings' => [
          'authorization_endpoint' => 'http://localhost/authorize',
          'token_endpoint' => 'http://localhost/token',
          'userinfo_endpoint' => 'http://localhost/userinfo',
          'end_session_endpoint' => 'http://localhost/endsession',
          'scopes' => ['openid email'],
          'client_secret' => 'test',
        ],
      ]
    );
    $client->save();

    return $client;
  }

  /**
   * Retrieve a test client.
   *
   * @return \Drupal\openid_connect\OpenIDConnectClientEntityInterface|null
   *   The test client.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getTestClient(
    string $clientId,
  ): OpenIDConnectClientEntityInterface {
    return \Drupal::service('entity_type.manager')
      ->getStorage('openid_connect_client')->load($clientId);
  }

  /**
   * Set the `redirect_logout` OpenID Setting.
   *
   * @param string $path
   *   The redirect path.
   */
  public function setRedirectLogoutUrl(string $path): void {
    $settingsConfig = \Drupal::configFactory()->getEditable('openid_connect.settings');
    $settingsConfig->set('redirect_logout', $path);
    $settingsConfig->save();
  }

  /**
   * Toggle the end session configuration setting.
   *
   * @param bool $enabled
   *   True for enabled, false for off.
   */
  public function toggleEndSessionSetting(bool $enabled): void {
    // Enable the end session endpoint.
    $settingsConfig = \Drupal::configFactory()->getEditable('openid_connect.settings');
    $settingsConfig->set('end_session_enabled', $enabled);
    $settingsConfig->save();
  }

}
