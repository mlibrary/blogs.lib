<?php

namespace Drupal\glightbox\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Component\Utility\Xss;
use Drupal\file\Entity\File;

/**
 * Service for GLightbox formatter functionality.
 */
class GLightboxFormatterService {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a GLightboxFormatterService object.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(
    Token $token_service,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config_factory
  ) {
    $this->tokenService = $token_service;
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * Get the caption for an image.
   *
   * @param array $variables
   *   An associative array containing:
   *   - item: An ImageItem object.
   *   - item_attributes: An optional associative array of html attributes to be
   *     placed in the img tag.
   *   - entity: An entity object.
   *   - settings: Formatter settings array.
   * @param string $caption_field
   *   The caption field to use.
   * @param bool $filter
   *   Whether to filter the caption text.
   *
   * @return string
   *   The caption text of the image parameter.
   */
  public function getCaption(array &$variables, string $caption_field = 'glightbox_caption', bool $filter = TRUE): string {
    $item = $variables['item'];
    $entity = $variables['entity'];
    $settings = $variables['settings'];

    // Build the caption.
    $entity_title = $entity->label();
    $entity_type = $entity->getEntityTypeId();

    switch ($settings[$caption_field]) {
      case 'auto':
        // If the title is empty use alt or the entity title in that order.
        if (!empty($item->title)) {
          $caption = $item->title;
        }
        elseif (!empty($item->alt)) {
          $caption = $item->alt;
        }
        elseif (!empty($entity_title)) {
          $caption = $entity_title;
        }
        else {
          $caption = '';
        }
        break;

      case 'title':
        $caption = $item->title;
        break;

      case 'alt':
        $caption = $item->alt;
        break;

      case 'entity_title':
        $caption = $entity_title;
        break;

      case 'custom':
        $data = [$entity_type => $entity, 'file' => $item];
        if (!empty($entity->_referringItem)) {
          $parent = $entity->_referringItem->getEntity();
          if ($entity_type != $parent->getEntityTypeId()) {
            // Add token for Entity -> Media.
            $data[$parent->getEntityTypeId()] = $parent;

            if ($parent->getEntityTypeId() == 'paragraph') {
              $entity_container = $parent->getParentEntity();
              if (!empty($entity_container) && $entity_container->getEntityTypeId() == 'node') {
                // Add token for Node -> Paragraph -> Media.
                $data['node'] = $entity_container;
              }
              elseif (!empty($entity_container) && $entity_container->getEntityTypeId() == 'paragraph') {
                // Add token for Paragraph -> Paragraph -> Media.
                $data['paragraph_container'] = $entity_container;
                $node_container = $entity_container->getParentEntity();
                if (!empty($node_container) && $node_container->getEntityTypeId() == 'node') {
                  // Add token for Node -> Paragraph -> Paragraph -> Media.
                  $data['node'] = $node_container;
                }
              }
            }
          }
          else {
            // Add token for Paragraph -> Paragraph -> Image.
            $data['paragraph_container'] = $parent;
            if ($parent->getEntityTypeId() == 'paragraph') {
              $entity_container = $parent->getParentEntity();
              if (!empty($entity_container) && $entity_container->getEntityTypeId() == 'node') {
                // Add token for Node -> Paragraph -> Paragraph -> Image.
                $data['node'] = $entity_container;
              }
            }
          }
        }
        $bubbleable_metadata = new BubbleableMetadata();
        $caption = $this->tokenService->replace(
          $settings["{$caption_field}_custom"],
          $data,
          ['clear' => TRUE],
          $bubbleable_metadata,
        );
        $bubbleable_metadata->applyTo($variables);
        break;

      default:
        $caption = '';
    }

    // If File Entity module is enabled, load attribute values from file entity.
    if ($this->moduleHandler->moduleExists('file_entity')) {
      // File id of the save file.
      $fid = $item->target_id;
      // Load file object.
      $file_obj = File::load($fid);
      $file_array = $file_obj->toArray();
      // Populate the image title.
      if (!empty($file_array['field_image_title_text'][0]['value']) && empty($item->title) && $settings[$caption_field] == 'title') {
        $caption = $file_array['field_image_title_text'][0]['value'];
      }
      // Populate the image alt text.
      if (!empty($file_array['field_image_alt_text'][0]['value']) && empty($item->alt) && $settings[$caption_field] == 'alt') {
        $caption = $file_array['field_image_alt_text'][0]['value'];
      }
    }

    // Shorten the caption for the example styles or when caption
    // shortening is active.
    $config = $this->configFactory->get('glightbox.settings');
    $glightbox_style = !empty($config->get('glightbox_style')) ? $config->get('glightbox_style') : '';
    $trim_length = $config->get('glightbox_caption_trim_length');
    if (((str_contains($glightbox_style, 'glightbox/example')) || $config->get('glightbox_caption_trim')) && (strlen($caption) > $trim_length)) {
      $caption = substr($caption, 0, $trim_length - 5) . '...';
    }

    if ($filter) {
      return Xss::filter($caption);
    }
    return $caption;
  }

}
