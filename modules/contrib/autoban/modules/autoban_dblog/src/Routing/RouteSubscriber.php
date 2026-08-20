<?php

namespace Drupal\autoban_dblog\Routing;

/**
 * @file
 * Contains \Drupal\autoban_dblog\Routing\RouteSubscriber.
 */

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route alter.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() : array {
    $events[RoutingEvents::ALTER] = [
      'onAlterRoutes',
      -176,
    ];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    // Change controller for '/admin/reports/dblog'.
    if ($route = $collection->get('dblog.overview')) {
      $route->setDefault('_controller', '\Drupal\autoban_dblog\Controller\AutobanDbLogController::overview');
    }
  }

}
