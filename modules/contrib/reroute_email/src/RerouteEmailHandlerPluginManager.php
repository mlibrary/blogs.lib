<?php

namespace Drupal\reroute_email;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides RerouteEmailHandler plugin manager.
 */
class RerouteEmailHandlerPluginManager extends DefaultPluginManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Constructs a RecipientHandlerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory,
  ) {
    parent::__construct(
      'Plugin/RerouteEmailHandler',
      $namespaces,
      $module_handler,
      'Drupal\reroute_email\RerouteEmailHandlerPluginInterface',
      'Drupal\reroute_email\Annotation\RerouteEmailHandler'
    );
    $this->setCacheBackend($cache_backend, 'reroute_email_handler_info');
    $this->alterInfo('reroute_email_handler_info');
    $this->configFactory = $config_factory;
  }

  /**
   * Apply all plugins by their type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function processAllByType(string $applied_type, &$email, bool $reroute_proceed = TRUE, bool $update_body = TRUE, ?array $reroute_settings = NULL) {
    // If no settings are provided, we apply all global settings.
    if ($reroute_settings === NULL) {
      $reroute_settings = $this->configFactory->get('reroute_email.settings')->get();
    }

    foreach ($this->getDefinitions() as $reroute_handler) {

      // Skip plugins not related to the specified type.
      if (!in_array($applied_type, $reroute_handler["applied"])) {
        continue;
      }

      /** @var \Drupal\reroute_email\RerouteEmailHandlerPluginBase $reroute_plugin */
      $reroute_plugin = $this->createInstance($reroute_handler['id'], [
        'settings' => $reroute_settings,
        'email' => &$email,
      ]);
      if ($reroute_proceed) {
        $reroute_plugin->process();
      }
      if ($update_body) {
        $reroute_plugin->processBody();
      }
    }
  }

}
