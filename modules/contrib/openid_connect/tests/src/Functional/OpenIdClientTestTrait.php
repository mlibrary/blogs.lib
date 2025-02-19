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
        'redirect_uri' => 'http://localhost',
        'grant_type' => 'authorization_code',
        'response_type' => 'code',
        'authorization_endpoint' => 'http://localhost/authorize',
        'token_endpoint' => 'http://localhost/token',
        'userinfo_endpoint' => 'http://localhost/userinfo',
        'jwks_uri' => 'http://localhost/jwks',
        'scopes' => ['openid email'],
        'client_secret' => 'test',
        'status' => TRUE,
      ]
    );
    $client->save();

    return $client;
  }

}
