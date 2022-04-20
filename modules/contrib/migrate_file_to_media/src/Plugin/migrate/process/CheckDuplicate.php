<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Available configuration keys
 * - migration: A single migration ID, or an array of migration IDs.
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
    if (isset($this->configuration['skip_method']) && $this->configuration['skip_method'] == 'process') {
      throw new MigrateSkipProcessException();
    }
    else {
      throw new MigrateSkipRowException();
    }
  }

}
