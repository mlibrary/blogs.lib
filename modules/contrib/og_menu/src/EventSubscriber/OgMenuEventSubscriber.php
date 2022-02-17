<?php

namespace Drupal\og_menu\EventSubscriber;

use Drupal\og\Event\PermissionEventInterface;
use Drupal\og\GroupPermission;
use Drupal\og\OgRoleInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscribers for OG Menu.
 */
class OgMenuEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      PermissionEventInterface::EVENT_NAME => [['provideDefaultOgPermissions']],
    ];
  }

  /**
   * Provides default OG permissions.
   *
   * @param \Drupal\og\Event\PermissionEventInterface $event
   *   The OG permission event.
   */
  public function provideDefaultOgPermissions(PermissionEventInterface $event) {
    // @todo Make granular per OG Menu instance.
    $event->setPermissions([
      new GroupPermission([
        'name' => 'add new links to og menu instance entities',
        'title' => t('Add new links to OG Menu instance entities'),
        'default roles' => [OgRoleInterface::ADMINISTRATOR],
      ]),
    ]);
  }

}
