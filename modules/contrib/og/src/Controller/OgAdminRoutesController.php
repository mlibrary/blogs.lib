<?php

declare(strict_types=1);

namespace Drupal\og\Controller;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\og\Event\OgAdminRoutesEvent;
use Drupal\og\Event\OgAdminRoutesEventInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The OG admin routes controller.
 */
class OgAdminRoutesController extends ControllerBase {

  public function __construct(
    protected readonly EventDispatcherInterface $eventDispatcher,
    protected readonly AccessManagerInterface $accessManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('access_manager')
    );
  }

  /**
   * Show all the available admin routes.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match service.
   *
   * @return array
   *   List of available admin routes for the current group.
   */
  public function overview(RouteMatchInterface $route_match) {
    $parameter_name = $route_match->getRouteObject()->getOption('_og_entity_type_id');

    /** @var \Drupal\Core\Entity\EntityInterface $group */
    $group = $route_match->getParameter($parameter_name);

    $entity_type_id = $group->getEntityTypeId();

    // Get list from routes.
    $content = [];

    $event = new OgAdminRoutesEvent();
    $event = $this->eventDispatcher->dispatch($event, OgAdminRoutesEventInterface::EVENT_NAME);

    foreach ($event->getRoutes($entity_type_id) as $name => $info) {
      $route_name = "entity.$entity_type_id.og_admin_routes.$name";
      $parameters = [$entity_type_id => $group->id()];

      // We don't use Url::fromRoute() here for the access check, as it will
      // prevent us from unit testing this method.
      if (!$this->accessManager->checkNamedRoute($route_name, $parameters)) {
        // User doesn't have access to the route.
        continue;
      }

      $content[$name]['title'] = $info['title'];
      $content[$name]['description'] = $info['description'];
      $content[$name]['url'] = Url::fromRoute($route_name, $parameters);
    }

    if (!$content) {
      return ['#markup' => $this->t('You do not have any administrative items.')];
    }

    return [
      'og_admin_routes' => [
        '#theme' => 'admin_block_content',
        '#content' => $content,
      ],
    ];
  }

}
