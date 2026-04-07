<?php

namespace Drupal\scheduler_rules_integration\Plugin\RulesAction;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a 'Remove date for scheduled unpublishing' action.
 *
 * SchedulerRulesActionsTest provides test coverage.
 *
 * @RulesAction(
 *   id = "scheduler_remove_unpublishing_date",
 *   deriver = "Drupal\scheduler_rules_integration\Plugin\RulesAction\SchedulerRulesActionDeriver"
 * )
 */
class RemoveUnpublishingDate extends SchedulerRulesActionBase {

  /**
   * Remove the unpublish_on date from the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which to remove the scheduled date.
   */
  public function doExecute(EntityInterface $entity) {
    $default_unpublish_enable = $this->schedulerManager->setting('default_unpublish_enable');
    $bundle_field = $entity->getEntityType()->get('entity_keys')['bundle'];
    if ($entity->$bundle_field->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $default_unpublish_enable)) {
      $entity->set('unpublish_on', NULL);
      scheduler_entity_presave($entity);
    }
    else {
      // The action cannot be executed because the content type is not enabled
      // for scheduled unpublishing.
      $this->notEnabledWarning($entity, 'unpublish');
    }
  }

}
