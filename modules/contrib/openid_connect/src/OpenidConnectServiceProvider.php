<?php

namespace Drupal\openid_connect;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service provider for the openid_connect module.
 *
 * @deprecated in openid_connect:3.0.0-alpha9 and is removed from
 *   openid_connect:4.0.0. There is no expected replacement as it is a
 *   helper to assist in upgrading 1.x to 3.x.
 *
 * @see https://www.drupal.org/project/openid_connect/issues/3537149
 */
class OpenidConnectServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // The externalauth services were introduced as a new dependency in version
    // 2.0alpha3, so a site upgrading straight from 1.x still has openid_connect
    // enabled while externalauth is not installed. The container has to compile
    // in that state, otherwise bootstrap dies with "You have requested a
    // non-existent service" and update.php never gets to run
    // openid_connect_update_8203(), which installs externalauth.
    if ($container->has('externalauth.authmap')) {
      return;
    }

    // No deprecation is triggered here. The only caller of this class is
    // Drupal's container builder, and the one situation in which this code path
    // runs - a half finished upgrade from 1.x - is exactly the situation the
    // class exists to rescue.
    foreach ($container->getDefinitions() as $id => $definition) {
      if (!$this->isOpenIdConnectService($id, $definition)) {
        continue;
      }
      foreach ($definition->getArguments() as $key => $argument) {
        if ($argument instanceof Reference && str_starts_with((string) $argument, 'externalauth.')) {
          $definition->replaceArgument($key, NULL);
        }
      }
    }
  }

  /**
   * Determines whether a service definition belongs to this module.
   *
   * Both the service ID and the class are checked, because openid_connect
   * declares its services under both a machine name and their fully qualified
   * class name, and the class is only resolved from the ID later on, during
   * compilation.
   *
   * @param string $id
   *   The service ID.
   * @param \Symfony\Component\DependencyInjection\Definition $definition
   *   The service definition.
   *
   * @return bool
   *   TRUE if the definition is one of this module's services.
   */
  protected function isOpenIdConnectService(string $id, Definition $definition): bool {
    $namespace = 'Drupal\openid_connect\\';
    return str_starts_with($id, 'openid_connect.')
      || str_starts_with($id, $namespace)
      || str_starts_with(ltrim((string) $definition->getClass(), '\\'), $namespace);
  }

}
