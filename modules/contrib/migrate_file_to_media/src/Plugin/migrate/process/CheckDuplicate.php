<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 *
 * @MigrateProcessPlugin(
 *   id = "check_duplicate"
 * )
 */
class CheckDuplicate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($value) {
      $query = \Drupal::database()->select('migrate_file_to_media_mapping', 'map');
      $query->fields('map');
      $query->condition('fid', $value, '=');
      $query->condition('target_fid', $value, '=');
      $result = $query->execute()->fetchObject();

      if (!empty($result->fid)) {
        return $result->fid;
      }
    }
    throw new MigrateSkipRowException();
  }

}
