<?php

namespace Drupal\openid_connect\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Provides a collection of OpenID Connect client plugins.
 */
class OpenIDConnectClientCollection extends DefaultSingleLazyPluginCollection {

  /**
   * The OpenID Connect client ID this plugin collection belongs to.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface
   */
  protected $clientId;

  /**
   * Constructs a new OpenIDConnectClientCollection.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param string $instance_id
   *   The ID of the plugin instance.
   * @param array $configuration
   *   An array of configuration.
   * @param string|null $openid_connect_client_id
   *   The unique ID of the OpenID Connect client entity using this plugin.
   */
  public function __construct(PluginManagerInterface $manager, string $instance_id, array $configuration, ?string $openid_connect_client_id) {
    if (!empty($openid_connect_client_id)) {
      $this->clientId = $openid_connect_client_id;
    }
    parent::__construct($manager, $instance_id, $configuration);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    if (!$instance_id) {
      throw new PluginException(sprintf("The OpenID Connect client %s did not specify a plugin.", $this->clientId));
    }

    parent::initializePlugin($instance_id);
    if (isset($this->clientId)) {
      /** @var \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface $plugin */
      $plugin = $this->get($instance_id);
      $plugin->setParentEntityId($this->clientId);
    }
  }

}
