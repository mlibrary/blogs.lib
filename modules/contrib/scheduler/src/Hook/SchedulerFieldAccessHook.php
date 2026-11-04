<?php

namespace Drupal\scheduler\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\scheduler\SchedulerManager;

/**
 * Controls access to the Scheduler date fields.
 */
class SchedulerFieldAccessHook {

  /**
   * The scheduler manager service.
   *
   * @var \Drupal\scheduler\SchedulerManager
   */
  protected $schedulerManager;

  /**
   * Constructs a SchedulerFieldAccessHook object.
   *
   * @param \Drupal\scheduler\SchedulerManager $scheduler_manager
   *   The scheduler manager service.
   */
  public function __construct(SchedulerManager $scheduler_manager) {
    $this->schedulerManager = $scheduler_manager;
  }

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess(string $operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResultInterface {
    $field_name = $field_definition->getName();
    if ($operation !== 'edit' || !in_array($field_name, ['publish_on', 'unpublish_on'], TRUE)) {
      return AccessResult::neutral();
    }
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    if (!in_array($entity_type_id, $this->schedulerManager->getPluginEntityTypes(), TRUE)) {
      return AccessResult::neutral();
    }

    // Setting the dates requires the scheduling permission for the entity type.
    $permission = $this->schedulerManager->permissionName($entity_type_id, 'schedule');
    if (!$account->hasPermission($permission)) {
      return AccessResult::forbidden();
    }

    // $items is NULL when access is checked for the field definition instead of
    // the field on a specific entity. The entity type settings require an
    // entity, so in that case only the permission applies.
    if ($items === NULL) {
      return AccessResult::neutral();
    }

    // The date can only be set when publishing or unpublishing is enabled for
    // the entity type.
    $setting = $field_name === 'publish_on' ? 'publish_enable' : 'unpublish_enable';
    if (!$this->schedulerManager->getThirdPartySetting($items->getEntity(), $setting, $this->schedulerManager->setting("default_$setting"))) {
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

}
