<?php

namespace Drupal\openid_connect\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines dynamic local tasks.
 */
class OpenIDConnectAddLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new OpenIDConnectAddLinks.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The plugin manager.
   */
  public function __construct(PluginManagerInterface $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.openid_connect_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $plugins = $this->pluginManager->getDefinitions();
    ksort($plugins);

    foreach ($plugins as $plugin_id => $plugin_definition) {
      $key = 'entity.openid_connect_client.add_form.' . $plugin_id;
      $this->derivatives[$key] = $base_plugin_definition;
      $this->derivatives[$key]['title'] = $plugin_definition['label'];
      $this->derivatives[$key]['route_name'] = 'entity.openid_connect_client.add_form';
      $this->derivatives[$key]['route_parameters']['plugin_id'] = $plugin_id;
    }
    return $this->derivatives;
  }

}
