<?php

declare(strict_types=1);

namespace Drupal\scheduler_no_bundle_test\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\scheduler_no_bundle_test\Form\SchedulerTestNoBundleForm;

/**
 * Scheduler-owned no-bundle entity type with publish/unpublish support.
 *
 * Self-contained entity type used in functional tests to exercise the
 * no-bundle scheduling code path. Intentionally non-revisionable so that
 * tests remain focused on no-bundle config and scheduling logic.
 *
 * @ContentEntityType(
 *   id = "scheduler_test_no_bundle",
 *   label = @Translation("Scheduler test entity - no bundle"),
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   handlers = {
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "form" = {
 *       "default" = "Drupal\scheduler_no_bundle_test\Form\SchedulerTestNoBundleForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/scheduler_test_no_bundle/{scheduler_test_no_bundle}",
 *     "add-form" = "/scheduler_test_no_bundle/add",
 *     "edit-form" = "/scheduler_test_no_bundle/{scheduler_test_no_bundle}/edit",
 *     "delete-form" = "/scheduler_test_no_bundle/{scheduler_test_no_bundle}/delete",
 *   },
 *   admin_permission = "administer scheduler_test_no_bundle content",
 *   base_table = "scheduler_test_no_bundle",
 * )
 */
#[ContentEntityType(
  id: 'scheduler_test_no_bundle',
  label: new TranslatableMarkup('Scheduler test entity - no bundle'),
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'label' => 'name',
    'langcode' => 'langcode',
    'published' => 'status',
  ],
  handlers: [
    'access' => EntityAccessControlHandler::class,
    'view_builder' => EntityViewBuilder::class,
    'form' => [
      'default' => SchedulerTestNoBundleForm::class,
      'delete' => ContentEntityDeleteForm::class,
    ],
    'route_provider' => ['html' => DefaultHtmlRouteProvider::class],
  ],
  links: [
    'canonical' => '/scheduler_test_no_bundle/{scheduler_test_no_bundle}',
    'add-form' => '/scheduler_test_no_bundle/add',
    'edit-form' => '/scheduler_test_no_bundle/{scheduler_test_no_bundle}/edit',
    'delete-form' => '/scheduler_test_no_bundle/{scheduler_test_no_bundle}/delete',
  ],
  admin_permission: 'administer scheduler_test_no_bundle content',
  base_table: 'scheduler_test_no_bundle',
)]
class EntityTestNoBundlePublishable extends ContentEntityBase implements EntityPublishedInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Name'))
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 32)
      ->setDisplayOptions('form', ['type' => 'string_textfield'])
      ->setDisplayConfigurable('form', TRUE);

    $fields += static::publishedBaseFieldDefinitions($entity_type);

    return $fields;
  }

}
