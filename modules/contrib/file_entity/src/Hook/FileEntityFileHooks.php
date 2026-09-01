<?php

namespace Drupal\file_entity\Hook;

use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for file_entity.
 */
class FileEntityFileHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_file_type().
   */
  #[Hook('file_type')]
  public static function fileType(FileInterface $file) {
    $types = [];
    foreach (FileType::loadEnabled() as $type) {
      if (file_entity_match_mimetypes($type->getMimeTypes(), $file->getMimeType())) {
        $types[] = $type->id();
      }
    }
    return $types;
  }

  /**
   * Implements hook_file_metadata_info().
   */
  #[Hook('file_metadata_info')]
  public function fileMetadataInfo() {
    $info['width'] = [
      'label' => $this->t('Width'),
      'type' => 'integer',
    ];
    $info['height'] = [
      'label' => $this->t('Height'),
      'type' => 'integer',
    ];
    return $info;
  }

}
