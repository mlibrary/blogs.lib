<?php

namespace Drupal\scheduler\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\scheduler\SchedulerManager;
use Symfony\Component\Routing\RouteCollection;

/**
 * Scheduler route subscriber to add custom access for user views.
 *
 * SchedulerViewsAccessTest provides test coverage for this functionality.
 */
class SchedulerRouteSubscriber extends RouteSubscriberBase {

  /**
   * The Scheduler manager service.
   *
   * @var \Drupal\scheduler\SchedulerManager
   */
  protected $schedulerManager;

  /**
   * Constructs a new SchedulerRouteSubscriber object.
   *
   * @param \Drupal\scheduler\SchedulerManager $scheduler_manager
   *   Scheduler manager service.
   */
  public function __construct(SchedulerManager $scheduler_manager) {
    $this->schedulerManager = $scheduler_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Access is controlled by \Drupal\scheduler\Access\SchedulerRouteAccess
    // via the scheduler.access_check service.
    $user_page_routes = $this->schedulerManager->getUserPageViewRoutes();
    foreach ($user_page_routes as $user_route) {
      if ($route = $collection->get($user_route)) {
        $route->setRequirement('_custom_access', 'scheduler.access_check::access');
      }
    }
  }

}
