<?php

namespace Drupal\openid_connect;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Service provider for the openid_connect module.
 */
class OpenidConnectServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // This service was introduced as a new dependency in version 2.0alpha3.
    try {
      $container->getDefinition('externalauth.authmap');
    }
    // If the service is not available, remove its dependent services.
    catch (ServiceNotFoundException $exception) {
      // Requires 'externalauth.authmap'.
      $container->removeDefinition('openid_connect.openid_connect');
    }
  }

}
