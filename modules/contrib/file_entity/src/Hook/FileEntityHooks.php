<?php

namespace Drupal\file_entity\Hook;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for file_entity.
 */
class FileEntityHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_hook_info_alter().
   *
   * Add support for existing core hooks to be located in modulename.file.inc.
   */
  #[Hook('info_alter')]
  public static function hookInfoAlter(&$info) {
    $hooks = [
          // File API hooks.
      'file_copy',
      'file_move',
      'file_validate',
          // File access.
      'file_download',
      'file_download_access',
      'file_download_access_alter',
          // File entity hooks.
      'file_load',
      'file_presave',
      'file_insert',
      'file_update',
      'file_delete',
          // Miscellaneous hooks.
      'file_mimetype_mapping_alter',
      'file_url_alter',
    ];
    $info += array_fill_keys($hooks, [
      'group' => 'file',
    ]);
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_match) {
      case 'entity.file_type.collection':
        $output = '<p>' . $this->t('When a file is uploaded to this website, it is assigned one of the following types, based on what kind of file it is.') . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_field_formatter_info_alter().
   */
  #[Hook('field_formatter_info_alter')]
  public function fieldFormatterInfoAlter(array &$info) {
    // Make the entity reference view formatter available for files and images.
    if (!empty($info['entity_reference_entity_view'])) {
      $info['entity_reference_entity_view']['field_types'][] = 'file';
      $info['entity_reference_entity_view']['field_types'][] = 'image';
    }
    // Add descriptions to core formatters.
    $descriptions = [
      'file_default' => $this->t('Create a simple link to the file. The link is prefixed by a file type icon and the name of the file is used as the link text.'),
      'file_table' => $this->t('Build a two-column table where the first column contains a generic link to the file and the second column displays the size of the file.'),
      'file_url_plain' => $this->t('Display a plain text URL to the file.'),
      'image' => $this->t('Format the file as an image. The image can be displayed using an image style and can optionally be linked to the image file itself or its parent content.'),
    ];
    foreach ($descriptions as $key => $description) {
      if (isset($info[$key]) && empty($info[$key]['description'])) {
        $info[$key]['description'] = $description;
      }
    }
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public static function theme() {
    return [
      'file' => [
        'render element' => 'elements',
        'template' => 'file',
      ],
      'file_entity_file_link' => [
        'variables' => [
          'file' => NULL,
          'icon_directory' => NULL,
        ],
        'file' => 'file_entity.theme.inc',
      ],
      'file_entity_download_link' => [
        'variables' => [
          'file' => NULL,
          'download_link' => NULL,
          'icon' => '',
          'file_size' => NULL,
          'attributes' => NULL,
        ],
      ],
      'file_entity_audio' => [
        'variables' => [
          'files' => [],
          'attributes' => NULL,
        ],
      ],
      'file_entity_video' => [
        'variables' => [
          'files' => [],
          'attributes' => NULL,
        ],
      ],
    ];
  }

  /**
   * Implements hook_entity_operation().
   */
  #[Hook('entity_operation')]
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity instanceof FileEntity && $entity->access('download')) {
      $operations['download'] = [
        'title' => $this->t('Download'),
        'weight' => 100,
        'url' => $entity->downloadUrl(),
      ];
    }
    return $operations;
  }

  /**
   * Implements hook_theme_suggestions_HOOK_alter().
   */
  #[Hook('theme_suggestions_file_alter')]
  public static function themeSuggestionsFileAlter(array &$suggestions, array $variables) {
    $view_mode = $variables['view_mode'] = $variables['elements']['#view_mode'];
    /** @var \Drupal\file\Entity\FileInterface $file */
    $file = $variables['elements']['#file'];
    // Clean up name so there are no underscores.
    $suggestions[] = 'file__' . $file->bundle();
    $suggestions[] = 'file__' . $file->bundle() . '__' . $view_mode;
    $suggestions[] = 'file__' . str_replace([
      '/',
      '-',
    ], [
      '__',
      '_',
    ], $file->getMimeType());
    $suggestions[] = 'file__' . str_replace([
      '/',
      '-',
    ], [
      '__',
      '_',
    ], $file->getMimeType()) . '__' . $view_mode;
    $suggestions[] = 'file__' . $file->id();
    $suggestions[] = 'file__' . $file->id() . '__' . $view_mode;
  }

  /**
   * Implements hook_file_download().
   */
  #[Hook('file_download')]
  public static function fileDownload($uri) {
    // Load the file from the URI.
    $file = file_uri_to_object($uri);
    // An existing file wasn't found, so we don't control access.
    // E.g. image derivatives will fall here.
    if (empty($file)) {
      return NULL;
    }
    // Allow the user to download the file if they have appropriate permissions.
    if ($file->access('view')) {
      return DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.2.0', fn() => $file->getDownloadHeaders(), fn() => file_get_content_headers($file));
    }
    return -1;
  }

  /**
   * @name pathauto_file Pathauto integration for the core file module.
   * @{
   */

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    // @todo Make this configurable and/or remove if
    //   https://drupal.org/node/476294 is resolved.
    if (\Drupal::moduleHandler()->moduleExists('pathauto') && $entity_type->id() == 'file') {
      $fields = [];
      $fields['path'] = BaseFieldDefinition::create('path')->setCustomStorage(TRUE)->setLabel($this->t('URL alias'))->setTranslatable(TRUE)->setProvider('file_entity')->setDisplayOptions('form', [
        'type' => 'path',
        'weight' => 30,
      ])->setDisplayConfigurable('form', TRUE);
      return $fields;
    }
  }

  /**
   * Implements hook_admin_menu_map().
   */
  #[Hook('admin_menu_map')]
  public static function adminMenuMap() {
    if (!user_access('administer file types')) {
      return;
    }
    $map['admin/structure/file-types/manage/%file_type'] = [
      'parent' => 'admin/structure/file-types',
      'arguments' => [
              [
                '%file_type' => array_keys(file_entity_type_get_names()),
              ],
      ],
    ];
    return $map;
  }

  /**
   * Implements hook_entity_storage_load().
   */
  #[Hook('entity_storage_load')]
  public static function entityStorageLoad($entities, $entity_type) {
    $token_service = \Drupal::token();
    $replace_options = [
      'clear' => TRUE,
      'sanitize' => FALSE,
    ];
    $config = \Drupal::config('file_entity.settings');
    // Loop over all the entities looking for entities with attached images.
    foreach ($entities as $entity) {
      // Skip non-fieldable entities.
      if (!$entity instanceof FieldableEntityInterface) {
        continue;
      }
      /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
      // Examine every image field instance attached to this entity's bundle.
      foreach ($entity->getFieldDefinitions() as $field_definition) {
        if ($field_definition->getSetting('target_type') == 'file' && $field_definition->getType() != 'image') {
          $field_name = $field_definition->getName();
          if (!empty($entity->{$field_name})) {
            foreach ($entity->{$field_name} as $item) {
              // If alt and title text is not specified, fall back to alt and
              // title text on the file.
              if (!empty($item->target_id) && (empty($item->alt) || empty($item->title))) {
                foreach ([
                  'alt',
                  'title',
                ] as $key) {
                  if (empty($item->{$key})) {
                    $token_bubbleable_metadata = new BubbleableMetadata();
                    $item->{$key} = $token_service->replace($config->get($key), [
                      'file' => $item->entity,
                    ], $replace_options, $token_bubbleable_metadata);
                    // Add the cacheability metadata of the token to the entity.
                    // This means attachments are discarded, but it does not ever
                    // make sense to have attachments for an image's "alt" and
                    // "title"attribute anyway, so this is acceptable.
                    $entity->addCacheableDependency($token_bubbleable_metadata);
                  }
                }
              }
            }
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_responsive_image_formatter().
   */
  #[Hook('preprocess_responsive_image_formatter')]
  public static function preprocessResponsiveImageFormatter(&$variables) {
    if (empty($variables['responsive_image']['#width']) || empty($variables['responsive_image']['#height'])) {
      foreach ([
        'width',
        'height',
      ] as $key) {
        $variables['responsive_image']["#{$key}"] = $variables['item']->entity->getMetadata($key);
      }
    }
  }

  /**
   * Implements hook_preprocess_image_formatter().
   */
  #[Hook('preprocess_image_formatter')]
  public static function preprocessImageFormatter(&$variables) {
    if (empty($variables['image']['#width']) || empty($variables['image']['#height'])) {
      foreach ([
        'width',
        'height',
      ] as $key) {
        $variables['image']["#{$key}"] = $variables['item']->entity->getMetadata($key);
      }
    }
  }

  /**
   * Implements hook_entity_bundle_info().
   */
  #[Hook('entity_bundle_info')]
  public function entityBundleInfo() {
    // Define the undefined bundle for validation to work when the type is not
    // yet known.
    $bundles['file']['undefined']['label'] = $this->t('Unknown');
    return $bundles;
  }

}
