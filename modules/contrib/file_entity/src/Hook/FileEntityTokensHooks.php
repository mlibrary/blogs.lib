<?php

namespace Drupal\file_entity\Hook;

use Drupal\Core\Url;
use Drupal\file_entity\Entity\FileType;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for file_entity.
 */
class FileEntityTokensHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    // File type tokens.
    $info['types']['file-type'] = [
      'name' => $this->t('File type'),
      'description' => $this->t('Tokens associated with file types.'),
      'needs-data' => 'file_type',
    ];
    $info['tokens']['file-type']['name'] = [
      'name' => $this->t('Name'),
      'description' => $this->t('The name of the file type.'),
    ];
    $info['tokens']['file-type']['machine-name'] = [
      'name' => $this->t('Machine-readable name'),
      'description' => $this->t('The unique machine-readable name of the file type.'),
    ];
    $info['tokens']['file-type']['count'] = [
      'name' => $this->t('File count'),
      'description' => $this->t('The number of files belonging to the file type.'),
    ];
    $info['tokens']['file-type']['edit-url'] = [
      'name' => $this->t('Edit URL'),
      'description' => $this->t("The URL of the file type's edit page."),
    ];
    // File tokens.
    $info['tokens']['file']['type'] = [
      'name' => $this->t('File type'),
      'description' => $this->t('The file type of the file.'),
      'type' => 'file-type',
    ];
    $info['tokens']['file']['download-url'] = [
      'name' => $this->t('Download URL'),
      'description' => $this->t('The URL to download the file directly.'),
      'type' => 'url',
    ];
    return $info;
  }

  /**
   * Implements hook_token_info_alter().
   */
  #[Hook('token_info_alter')]
  public function tokenInfoAlter(&$info) {
    $info['tokens']['file']['name']['description'] = $this->t('The name of the file.');
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public static function tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
    $replacements = [];
    $url_options = [
      'absolute' => TRUE,
    ];
    if (isset($options['langcode'])) {
      $langcode = $options['langcode'];
      $url_options['language'] = \Drupal::languageManager()->getLanguage($langcode);
    }
    else {
      $langcode = NULL;
    }
    // File tokens.
    if ($type == 'file' && !empty($data['file'])) {
      $file = $data['file'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'type':
            if ($file_type = FileType::load($file->bundle())) {
              $bubbleable_metadata->addCacheableDependency($file_type);
              $replacements[$original] = $file_type->label();
            }
            break;

          case 'download-url':
            $replacements[$original] = $file->downloadUrl($url_options)->toString();
            break;
        }
      }
      // Chained token relationships.
      $token_service = \Drupal::service('token');
      if (($file_type_tokens = $token_service->findWithPrefix($tokens, 'type')) && $file_type = FileType::load($file->bundle())) {
        $replacements += $token_service->generate('file-type', $file_type_tokens, [
          'file_type' => $file_type,
        ], $options, $bubbleable_metadata);
      }
      if ($download_url_tokens = $token_service->findWithPrefix($tokens, 'download-url')) {
        $replacements += $token_service->generate('url', $download_url_tokens, $file->downloadUrl()->toString(), $options, $bubbleable_metadata);
      }
    }
    // File type tokens.
    if ($type == 'file-type' && !empty($data['file_type'])) {
      $file_type = $data['file_type'];
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'name':
            $replacements[$original] = $file_type->label();
            break;

          case 'machine-name':
            // This is a machine name so does not ever need to be sanitized.
            $replacements[$original] = $file_type->id();
            break;

          case 'count':
            $query = \Drupal::database()->select('file_managed');
            $query->condition('type', $file_type->id());
            $query->addTag('file_type_file_count');
            $count = $query->countQuery()->execute()->fetchField();
            $replacements[$original] = (int) $count;
            break;

          case 'edit-url':
            $replacements[$original] = Url::fromUri('admin/structure/file-types/manage/' . $file_type->type . '/fields', $url_options)->toString();
            break;
        }
      }
    }
    return $replacements;
  }

}
