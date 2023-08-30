<?php

namespace Drupal\openid_connect\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to alter core user routes.
 *
 * @package Drupal\openid_connect\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Reroute the default user.logout route.
    if ($route = $collection->get('user.logout')) {
      $route->setDefault('_controller', '\Drupal\openid_connect\Controller\OpenIDConnectRedirectController::redirectLogout');
    }
  }

}
