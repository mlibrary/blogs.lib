<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 *
 * @MigrateProcessPlugin(
 *   id = "file_id_lookup"
 * )
 */
class FileIdLookup extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $fid = null;
    if (!empty($value)) {
      if (is_array($value)) {
        $fid = !empty($value['target_id']) ? $value['target_id'] : $value['fid'];
      }
      else {
        $fid = $value;
      }
    }

    if ($fid) {
      $query = \Drupal::database()->select('migrate_file_to_media_mapping', 'map');
      $query->fields('map');
      $query->condition('fid', $fid, '=');
      $result = $query->execute()->fetchObject();

      if ($result) {
        // If the record has an existing media entity, return it.
        if (!empty($result->media_id)) {
          return $result->media_id;
        }

        return parent::transform($result->target_fid, $migrate_executable, $row, $destination_property);
      }
    }

    if (isset($this->configuration['skip_method']) && $this->configuration['skip_method'] == 'process') {
      throw new MigrateSkipProcessException();
    }
    else {
      throw new MigrateSkipRowException();
    }
  }

}
