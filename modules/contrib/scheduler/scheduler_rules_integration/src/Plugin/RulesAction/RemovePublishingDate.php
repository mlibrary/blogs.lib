<?php

namespace Drupal\scheduler_rules_integration\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a 'Remove date for scheduled publishing' action.
 *
 * SchedulerRulesActionsTest provides test coverage.
 *
 * @RulesAction(
 *   id = "scheduler_remove_publishing_date",
 *   deriver = "Drupal\scheduler_rules_integration\Plugin\RulesAction\SchedulerRulesActionDeriver"
 * )
 */
class RemovePublishingDate extends SchedulerRulesActionBase {

  /**
   * Remove the publish_on date from the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to remove the scheduled date.
   */
  public function doExecute(EntityInterface $entity) {
    $default_publish_enable = $this->schedulerManager->setting('default_publish_enable');
    $bundle_field = $entity->getEntityType()->get('entity_keys')['bundle'];
    if ($entity->$bundle_field->entity->getThirdPartySetting('scheduler', 'publish_enable', $default_publish_enable)) {
      $entity->set('publish_on', NULL);
      scheduler_entity_presave($entity);
    }
    else {
      // The action cannot be executed because the content type is not enabled
      // for scheduled publishing.
      $this->notEnabledWarning($entity, 'publish');
    }
  }

}
