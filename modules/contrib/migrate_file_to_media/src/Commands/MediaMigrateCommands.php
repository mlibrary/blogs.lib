<?php

namespace Drupal\migrate_file_to_media\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drush\Commands\DrushCommands;

/**
 * Drush 9 commands for migrate_file_to_media.
 */
class MediaMigrateCommands extends DrushCommands {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * Migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $migrationPluginManager;

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * MediaMigrateCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(
    EntityFieldManagerInterface $entityFieldManager,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    MigrationPluginManager $migrationPluginManager,
    StreamWrapperManagerInterface $stream_wrappers,
    FileSystemInterface $file_system
  ) {
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;
    $this->migrationPluginManager = $migrationPluginManager;
    $this->streamWrapperManager = $stream_wrappers;
    $this->fileSystem = $file_system;
  }

  /**
   * Create media destination fields.
   *
   * @command migrate:file-media-fields
   * @aliases mf2m
   *
   * @param $entity_type
   * @param $bundle
   * @param $source_field_type
   * @param $target_media_bundle
   */
  public function migrateFileFields($entity_type, $bundle, $source_field_type, $target_media_bundle) {

    $this->output()
      ->writeln("Creating media reference fields for {$entity_type} : {$bundle}.");

    $bundle_fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);

    // Gather a list of all target fields.
    $map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType($source_field_type);
    $source_fields = [];
    foreach ($map[$entity_type] as $name => $data) {
      foreach ($data['bundles'] as $bundle_name) {
        if ($bundle_name == $bundle) {
          $target_field_name = substr($name, 0, FieldStorageConfig::NAME_MAX_LENGTH - 6) . '_media';
          $source_fields[$target_field_name] = $bundle_fields[$name];
          $this->output()->writeln('Found field: ' . $name);
        }
      }
    }

    $map = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference');
    $media_fields = [];
    foreach ($map[$entity_type] as $name => $data) {
      foreach ($data['bundles'] as $bundle_name) {
        if ($bundle_name == $bundle) {
          $field_settings = $bundle_fields[$name];
          $target_bundles = $field_settings->getSettings()['handler_settings']['target_bundles'];
          $handler = $field_settings->getSettings()['handler'];
          if (!empty($target_bundles)) {
            foreach ($target_bundles as $target_bundle) {
              if ($handler == 'default:media' && $target_bundle == $target_media_bundle) {
                // $media_fields[$name] = $field_settings;.
                $this->output()
                  ->writeln('Found existing media field: ' . $name);
              }
            }
          }
        }
      }
    }

    // Create missing fields.
    $missing_fields = array_diff_key($source_fields, $media_fields);

    foreach ($missing_fields as $new_field_name => $field) {
      try {
        $new_field = $this->createMediaField(
          $entity_type,
          $bundle,
          $field,
          $new_field_name,
          $target_media_bundle
        );
      }
      catch (\Exception $ex) {
        $this->output()
          ->writeln("Error while creating media field: {$new_field_name}.");
      }

      if (!empty($new_field)) {
        $this->output()
          ->writeln("Created media field: {$new_field->getName()}.");
      }
    }
    $this->output()->writeln("Clearing caches.");
    drupal_flush_all_caches();

  }

  /**
   * Create a new entity media reference field.
   *
   * @param $entity_type
   * @param $bundle
   * @param \Drupal\field\Entity\FieldConfig $existing_field
   * @param $new_field_name
   * @param $target_media_bundle
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface
   */
  private function createMediaField(
    $entity_type,
    $bundle,
    FieldConfig $existing_field,
    $new_field_name,
    $target_media_bundle
  ) {
    $field = FieldConfig::loadByName($entity_type, $bundle, $new_field_name);
    if (empty($field)) {

      // Load existing field storage.
      $field_storage = FieldStorageConfig::loadByName($entity_type, $new_field_name);

      // Create a field storage if none found.
      if (empty($field_storage)) {
        $field_storage = FieldStorageConfig::create(
          [
            'field_name' => $new_field_name,
            'entity_type' => $entity_type,
            'cardinality' => $existing_field->getFieldStorageDefinition()
              ->getCardinality(),
            'type' => 'entity_reference',
            'settings' => ['target_type' => 'media'],
          ]
        );
        $field_storage->save();
      }

      $field = \Drupal::service('entity_type.manager')->getStorage('field_config')->create([
        'field_storage' => $field_storage,
        'bundle' => $bundle,
        'label' => $existing_field->getLabel() . ' Media',
        'settings' => [
          'handler' => 'default:media',
          'handler_settings' => ['target_bundles' => [$target_media_bundle => $target_media_bundle]],
        ],
      ]);
      $field->save();

      // Update Form Widget.
      $type = $entity_type . '.' . $bundle . '.default';
      /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $definition */
      $definition = $this->entityTypeManager->getStorage('entity_form_display')
        ->load($type);
      $definition->setComponent(
        $new_field_name,
        [
          'type' => 'entity_reference_autocomplete',
        ]
      )->save();

    }
    return $field;
  }

  /**
   * Find duplicate file entities.
   *
   * @command migrate:duplicate-file-detection
   * @aliases migrate-duplicate
   *
   * @param $migration_name
   *
   * @option check-existing-media Check for existing media
   */
  public function duplicateImageDetection($migration_name, $options = ['check-existing-media' => FALSE]) {

    $manager = $this->migrationPluginManager;
    $plugins = $manager->createInstances([]);

    /** @var \Drupal\migrate\Plugin\Migration $migration_instance */
    $migration_instance = NULL;
    foreach ($plugins as $id => $migration) {
      if (in_array(mb_strtolower($id), [$migration_name])) {
        $migration_instance = $migration;
      }
    }

    // Force update
    $migration_instance->getIdMap()->prepareUpdate();

    // Use the migration source plugin to calculate the binary hash of
    // the related files only.
    $source = $migration_instance->getSourcePlugin();
    $source->rewind();

    while ($source->valid()) {
      $row = $source->current();

      // Support remote images.
      if (!$this->isLocalUri($row->getSourceProperty('file_path'))) {
        $file = File::create([
          'fid' => $row->getSourceProperty('target_id'),
          'uri' => $row->getSourceProperty('file_path'),
        ]);
      }
      else {
        /** @var \Drupal\file\Entity\File $file */
        $file = File::load($row->getSourceProperty('target_id'));
      }

      if (!$file) {
        $source->next();
        $this->output()
          ->writeln(dt("File not found: Skipped binary hash for source {$row->getSourceProperty('target_id')}"));
        continue;
      }

      try {

        // Skip existing entries is command is run multiple times.
        $skip_processed = $this->connection->select('migrate_file_to_media_mapping', 'map');
        $skip_processed->fields('map');
        $skip_processed->condition('fid', $file->id(), '=');
        $skip_processed->condition('migration_id', $migration_instance->getPluginId(), '=');
        $skip_processed = $skip_processed->execute()->fetchObject();

        if (!empty($skip_processed)) {
          $this->output()->writeln(dt("File {$file->id()} already processed."));
          $source->next();
          continue;
        }

        if (!empty($binary_hash = $this->calculateBinaryHash($file))) {
          // Query for duplicates.
          $query = $this->connection->select('migrate_file_to_media_mapping', 'map');
          $query->fields('map');
          $query->condition('binary_hash', $binary_hash, '=');
          $result = $query->execute()->fetchObject();

          $duplicate_fid = $file->id();
          if ($result) {
            $existing_file = File::load($result->fid);
            if (!empty($existing_file)) {
              $duplicate_fid = $existing_file->id();
              $this->output()
                ->writeln("Duplicate found for file {$existing_file->id()}");
            }
          }

          $existing_media = NULL;

          // Check for existing media entities from previous migrations.
          if ($options['check-existing-media']) {
            // Check for an existing media entity.
            $query_media = $this->connection->select('migrate_file_to_media_mapping_media', 'media');
            $query_media->fields('media');
            $query_media->condition('binary_hash', $binary_hash, '=');
            $existing_media = $query_media->execute()->fetchObject();
          }

          $this->connection->insert('migrate_file_to_media_mapping')
            ->fields([
              'type' => 'image',
              'migration_id' => $migration_instance->getPluginId(),
              'fid' => $file->id(),
              'target_fid' => $duplicate_fid,
              'binary_hash' => $binary_hash,
              'media_id' => $existing_media ? $existing_media->entity_id : NULL,
            ])
            ->execute();

          $this->output()
            ->writeln(dt("Added binary hash {$binary_hash} for file {$file->id()}"));
        }
        else {
          $this->output()
            ->writeln(dt("File empty: Skipped binary hash for file {$file->id()}"));
        }
      }
      catch (\Exception $ex) {
        $this->output()
          ->writeln(dt("File not found: Skipped binary hash for file {$file->id()}"));
      }

      $source->next();
    }
  }

  /**
   * Calculate hash values of media entities. Can later be used together with
   * migrate:duplicate-file-detection to find existing media files.
   *
   * @command migrate:duplicate-media-detection
   * @aliases migrate-duplicate-media
   *
   * @param string $bundle
   *   Optional media bundle, default = image.
   * @param string $field
   *   Optional media file field, default = field_media_image.
   *
   * @option all Parse all existing media files or set to 0 to only process
   *   media items from previous imports.
   */
  public function duplicateMediaImageDetection($bundle = 'image', $field = 'field_media_image', $options = ['all' => TRUE]) {

    // Only query permanent files.
    $query = $this->connection->select('media', 'me');
    $query->fields('me', ['mid']);
    if (intval($options['all']) === 0) {
      $query->leftJoin('migrate_file_to_media_mapping_media', 'm', 'm.entity_id = me.mid');
    }
    $query->condition('bundle', $bundle);

    $mids = array_map(
      function ($mid) {
        return $mid->mid;
      },
      $query->execute()->fetchAll()
    );

    $medias = Media::loadMultiple($mids);

    if (empty($medias)) {
      $this->output()
        ->writeln("No media found.");
    }

    else {
      foreach ($medias as $media) {
        /** @var \Drupal\media\Entity\Media $media */
        try {
          /** @var \Drupal\file\Entity\File $file */
          $file = $media->$field->entity;

          if ($file && !empty($binary_hash = $this->calculateBinaryHash($file))) {

            $query = $this->connection->select('migrate_file_to_media_mapping_media', 'map');
            $query->fields('map');
            $query->condition('binary_hash', $binary_hash);
            $result = $query->execute()->fetchObject();

            $duplicate_id = $media->id();
            if ($result) {
              $existing_media = Media::load($result->entity_id);
              if ($existing_media) {
                $duplicate_id = $existing_media->id();
                $this->output()
                  ->writeln("Duplicate found for file {$existing_media->id()}");
              }
            }

            // Check if we have an existing mapping. Update matching record.
            $upsert_query = $this->connection->select('migrate_file_to_media_mapping_media', 'map');
            $upsert_query->fields('map');
            $upsert_query->condition('binary_hash', $binary_hash);
            $current_mapping = $upsert_query->execute()->fetchObject();
            if ($current_mapping) {
              $this->connection->update('migrate_file_to_media_mapping_media')
                ->condition('id', $current_mapping->id)
                ->fields([
                  'media_bundle' => $bundle,
                  'fid' => $file->id(),
                  'entity_id' => $media->id(),
                  'target_entity_id' => $duplicate_id,
                  'binary_hash' => $binary_hash,
                ])
                ->execute();

              $this->output()
                ->writeln("Updated binary hash {$binary_hash} for media {$media->id()}");
            }
            else {
              // Insert new mappings for existing media entities.
              $this->connection->insert('migrate_file_to_media_mapping_media')
                ->fields([
                  'media_bundle' => $bundle,
                  'fid' => $file->id(),
                  'entity_id' => $media->id(),
                  'target_entity_id' => $duplicate_id,
                  'binary_hash' => $binary_hash,
                ])
                ->execute();

              $this->output()
                ->writeln("Added binary hash {$binary_hash} for media {$media->id()}");
            }
          }
          else {
            $this->output()
              ->writeln("Media empty: Skipped binary hash for media {$media->id()}");
          }
        }
        catch (\Exception $ex) {
          $this->output()
            ->writeln("Media not found: Skipped binary hash for media {$media->id()}. Exception: {$ex->getMessage()}");
        }
      }
    }
  }

  private function calculateBinaryHash(File $file) {

    // For rokka files, we can use the metadata table to get the correct
    // binary_hash value.
    $rokka_metadata = NULL;
    if (strpos($file->getFileUri(), 'rokka://') === 0) {
      $query = $this->connection->select('rokka_metadata', 'rokka');
      $query->fields('rokka');
      $query->condition('uri', $file->getFileUri(), '=');
      $rokka_metadata = $query->execute()->fetchObject();
      $data = 'empty';
    }
    else {
      $data = file_get_contents(urldecode($file->getFileUri()));
    }

    $binary_hash = NULL;
    if (!empty($data)) {
      $binary_hash = $rokka_metadata ? $rokka_metadata->binary_hash : sha1($data);
    }

    return $binary_hash;
  }

  /**
   * Determines if the given URI or path is considered local.
   *
   * A URI or path is considered local if it either has no scheme component,
   * or the scheme is implemented by a stream wrapper which extends
   * \Drupal\Core\StreamWrapper\LocalStream.
   *
   * @param string $uri
   *   The URI or path to test.
   *
   * @return bool
   */
  private function isLocalUri($uri) {
    $scheme = $this->streamWrapperManager->getScheme($uri);

    // The vfs scheme is vfsStream, which is used in testing. vfsStream is a
    // simulated file system that exists only in memory, but should be treated
    // as a local resource.
    if ($scheme == 'vfs') {
      $scheme = FALSE;
    }
    return $scheme === FALSE || $this->streamWrapperManager->getViaScheme($scheme) instanceof LocalStream;
  }

}
