<?php

namespace Drupal\openid_connect;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;

/**
 * OpenID Connect client entity interface.
 */
interface OpenIDConnectClientEntityInterface extends ConfigEntityInterface, EntityWithPluginCollectionInterface {

  /**
   * Returns the plugin instance.
   *
   * @return \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface
   *   The plugin instance for this OpenID Connect client.
   */
  public function getPlugin(): OpenIDConnectClientInterface;

  /**
   * Returns the plugin ID.
   *
   * @return string
   *   The plugin ID for this OpenID Connect client.
   */
  public function getPluginId(): string;

}
