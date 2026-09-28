<?php

namespace Drupal\Tests\scheduler\Traits;

use Drupal\Core\Entity\EntityInterface;

/**
 * Additional setup trait for Scheduler tests that use scheduler_test_no_bundle.
 *
 * This builds on the standard SchedulerSetupTrait. Unlike bundled entity type
 * traits, there is no bundle type to create — scheduling is enabled via the
 * no_bundle_entity_type_settings config directly on the entity type.
 */
trait SchedulerNoBundleEntitySetupTrait {

  /**
   * The scheduler_test_no_bundle entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $schedulerTestNoBundleStorage;

  /**
   * Set up the scheduler_test_no_bundle entity type for scheduling tests.
   */
  public function schedulerNoBundleEntitySetUp(): void {
    // Enable scheduling via no_bundle config (no bundle type object exists).
    $this->container->get('config.factory')
      ->getEditable('scheduler.no_bundle_entity_type_settings.scheduler_test_no_bundle')
      ->set('publish_enable', TRUE)
      ->set('unpublish_enable', TRUE)
      ->save();

    // Enable the scheduler fields in the default form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('scheduler_test_no_bundle', 'scheduler_test_no_bundle')
      ->setComponent('publish_on', ['type' => 'datetime_timestamp_no_default'])
      ->setComponent('unpublish_on', ['type' => 'datetime_timestamp_no_default'])
      ->save();

    $this->schedulerTestNoBundleStorage = $this->container->get('entity_type.manager')
      ->getStorage('scheduler_test_no_bundle');

    $this->addPermissionsToUser($this->adminUser, [
      'schedule publishing of scheduler_test_no_bundle',
      'view scheduled scheduler_test_no_bundle',
      'administer scheduler_test_no_bundle content',
    ]);

    $this->addPermissionsToUser($this->schedulerUser, [
      'schedule publishing of scheduler_test_no_bundle',
      'administer scheduler_test_no_bundle content',
    ]);
  }

  /**
   * Creates a scheduler_test_no_bundle entity.
   *
   * @param array $values
   *   Values for the entity. 'title' is normalized to 'name'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created entity.
   */
  public function createEntityTestEntity(array $values = []): EntityInterface {
    if (isset($values['title'])) {
      $values['name'] = $values['title'];
      unset($values['title']);
    }
    $values += ['name' => $this->randomMachineName(), 'status' => TRUE];
    $entity = $this->schedulerTestNoBundleStorage->create($values);
    $entity->save();
    return $entity;
  }

  /**
   * Gets a scheduler_test_no_bundle entity from storage.
   *
   * If no name is given the entity with the highest id (newest) is returned.
   *
   * @param string|null $name
   *   Optional name to match. Returns NULL if no match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object, or NULL.
   */
  public function getEntityTestEntity(?string $name = NULL): ?EntityInterface {
    $query = $this->schedulerTestNoBundleStorage->getQuery()->accessCheck(FALSE);
    if ($name) {
      $query->condition('name', $name);
    }
    else {
      $query->sort('id', 'DESC')->range(0, 1);
    }
    $ids = $query->execute();
    return $ids ? $this->schedulerTestNoBundleStorage->load(reset($ids)) : NULL;
  }

}
