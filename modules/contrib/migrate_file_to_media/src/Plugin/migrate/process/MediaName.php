<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 *
 * @MigrateProcessPlugin(
 *   id = "media_name"
 * )
 */
class MediaName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($source, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Use alt tag if available.
    if (!empty($row->getSourceProperty('alt'))) {
      return mb_substr($row->getSourceProperty('alt'), 0, 255);
    }
    // Use filename as fallback.
    elseif (!empty($source)) {
      return $source;
    }
    return '';
  }

}
