<?php

declare(strict_types=1);

namespace Drupal\openid_connect_test\Plugin\OpenIDConnectClient;

use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * Client plugin whose machine name contains a dash.
 *
 * Represents the 1.x scenario from issue #3359789 where a provider registered
 * its redirect URI with a dash (e.g. the Belgian government's "acm-idm"). The
 * upgrade path must preserve the dash in provider_slug while sanitizing the
 * config entity machine name.
 *
 * @OpenIDConnectClient(
 *   id = "acm-idm",
 *   label = @Translation("Test dash client")
 * )
 */
class OpenIDConnectTestDashClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(): array {
    return [
      'authorization' => $this->configuration['authorization_endpoint'],
      'token' => $this->configuration['token_endpoint'],
      'userinfo' => $this->configuration['userinfo_endpoint'],
      'end_session' => $this->configuration['end_session_endpoint'] ?? '',
    ];
  }

}
