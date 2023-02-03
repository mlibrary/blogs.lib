<?php

namespace Drupal\scheduler_content_moderation_integration\EventSubscriber;

use Drupal\scheduler\SchedulerEvent;
use Drupal\scheduler\SchedulerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * React to the PUBLISH_IMMEDIATELY scheduler event.
 */
class SchedulerEventSubscriber implements EventSubscriberInterface {

  /**
   * Operations to perform after Scheduler publishes an entity immediately.
   *
   * This is during the edit process, not via cron.
   *
   * @param \Drupal\scheduler\SchedulerEvent $event
   *   The event being acted on.
   */
  public function publishImmediately(SchedulerEvent $event) {
    /** @var Drupal\Core\Entity\EntityInterface $entity */
    $entity = $event->getNode();
    $entity->set('moderation_state', $entity->publish_state->getValue());
    $event->setNode($entity);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // The values in the arrays give the function names above. The same function
    // can be used for all supported entity types.
    $events[SchedulerEvents::PUBLISH_IMMEDIATELY][] = ['publishImmediately'];
    return $events;
  }

}
