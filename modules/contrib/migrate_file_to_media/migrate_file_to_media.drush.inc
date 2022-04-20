<?php

/**
 * @file
 * Backports the Drush commands to Drush 8
 */

/**
 * Implements hook_drush_command().
 */
function migrate_file_to_media_drush_command() {
  $items = [];

  $items['migrate-file-media-fields'] = [
    'description' => 'Create media destination fields. (migrate-file-media-fields)',
    'arguments' => [
      'entity_type' => '',
      'bundle' => '',
      'source_field_type' => '',
      'target_media_bundle' => '',
    ],
    'drupal dependencies' => ['migrate_file_to_media'],
    'aliases' => ['mf2m'],
  ];

  $items['migrate-duplicate-file-detection'] = [
    'description' => 'Find duplicate file entities. (migrate-duplicate-file-detection)',
    'arguments' => ['name' => 'The migration name'],
    'options' => ['check-existing-media' => ''],
    'drupal dependencies' => ['migrate_file_to_media'],
    'aliases' => ['migrate-duplicate'],
  ];

  $items['migrate-duplicate-media-detection'] = [
    'description' => 'Calculate hash values of media entities. (migrate-duplicate-media-detection)',
    'arguments' => [
      'bundle' => 'Optional media bundle, default = image',
      'field' => 'Optional media file field, default = field_media_image.',
    ],
    'options' => ['all' => ''],
    'drupal dependencies' => ['migrate_file_to_media'],
    'aliases' => ['migrate-duplicate-media'],
  ];

  return $items;
}

/**
 * Call back function migrate-file-media-fields
 */
function drush_migrate_file_to_media_migrate_file_media_fields($entity_type, $bundle, $source_field_type, $target_media_bundle) {
  /** @var \Drupal\migrate_file_to_media\Commands\MediaMigrateCommands $migrate_file_to_media */
  $migrate_file_to_media = \Drupal::service('migrate_file_to_media.commands');
  $migrate_file_to_media->migrateFileFields($entity_type, $bundle, $source_field_type, $target_media_bundle);
}

/**
 * Call back function migrate-duplicate-file-detection
 */
function drush_migrate_file_to_media_migrate_duplicate_file_detection($name) {
  $options = ['check-existing-media' => drush_get_option('check-existing-media', FALSE)];

  /** @var \Drupal\migrate_file_to_media\Commands\MediaMigrateCommands $migrate_file_to_media */
  $migrate_file_to_media = \Drupal::service('migrate_file_to_media.commands');
  $migrate_file_to_media->duplicateImageDetection($name, $options);
}

/**
 * Call back function migrate-duplicate-media-detection
 */
function drush_migrate_file_to_media_migrate_duplicate_media_detection($bundle = 'image', $field = 'field_media_image') {
  $options = ['all' => drush_get_option('all', TRUE)];

  /** @var \Drupal\migrate_file_to_media\Commands\MediaMigrateCommands $migrate_file_to_media */
  $migrate_file_to_media = \Drupal::service('migrate_file_to_media.commands');
  $migrate_file_to_media->duplicateMediaImageDetection($bundle, $field, $options);
}
