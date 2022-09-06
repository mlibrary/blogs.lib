<?php

namespace Drupal\passwordless\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('user.login')) {
      $route->setDefaults(['_form' => '\Drupal\passwordless\Form\PasswordlessLoginForm']);
    }

    if ($route = $collection->get('user.pass')) {
      $route->setDefaults(['_controller' => '\Drupal\passwordless\Controller\PasswordlessController::redirectUserPassPage']);
    }

    if ($route = $collection->get('user.reset')) {
      $route->setDefaults([
        '_controller' => '\Drupal\passwordless\Controller\PasswordlessUserController::resetPass',
        '_title' => 'Log-in',
      ]);
    }
  }
}