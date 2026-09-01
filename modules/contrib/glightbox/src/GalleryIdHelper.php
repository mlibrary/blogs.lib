<?php

namespace Drupal\glightbox;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Utility\Token;

/**
 * Implementation of GalleryIdHelper.
 *
 * @package Drupal\glightbox
 */
class GalleryIdHelper {

  /**
   * The Gallery Token.
   *
   * @var null
   */
  protected $galleryToken = NULL;

  /**
   * The Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * GalleryIdHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The Configuration Factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The Token.
   */
  public function __construct(ConfigFactoryInterface $configFactory, Token $token) {
    $this->configFactory = $configFactory;
    $this->token = $token;
  }

  /**
   * Generate ID.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The Entity.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The Item.
   * @param array $settings
   *   The Settings.
   * @param string $parent_entity_id,
   *   The Parent entity ID.
   * @param string $parent_paragraph_id,
   *   The Parent ID of paragraph.
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   Optional bubbleable metadata to collect cache metadata from token replacement.
   *
   * @return string
   *   Return string.
   */
  public function generateId(ContentEntityInterface $entity, FieldItemInterface $item, array $settings, $parent_entity_id = '', $parent_paragraph_id = '', ?BubbleableMetadata $bubbleable_metadata = NULL) {
    $entity_bundle = $entity->bundle();
    $entity_type = $entity->getEntityTypeId();
    $config = $this->configFactory->get('glightbox.settings');

    // Build the gallery id.
    $id = $entity->id();
    $entity_id = !empty($id) ? $entity_bundle . '-' . $id : 'entity-id';
    $field_name = $item->getParent()->getName();

    switch ($settings['glightbox_gallery']) {
      case 'post':
        $gallery_id = 'gallery-' . $entity_id;
        break;

      case 'page':
        $gallery_id = 'gallery-all';
        break;

      case 'parent':
        $gallery_id = 'gallery-parent-entity-' . $parent_entity_id;
        break;

      case 'paragraph':
        $gallery_id = 'gallery-parent-paragraph-' . $parent_paragraph_id;
        break;

      case 'field_post':
        $gallery_id = 'gallery-' . $entity_id . '-' . $field_name;
        break;

      case 'field_page':
        $gallery_id = 'gallery-' . $field_name;
        break;

      case 'custom':
        $gallery_id = $this->token->replace(
          $settings['glightbox_gallery_custom'],
          [$entity_type => $entity, 'file' => $item],
          ['clear' => TRUE],
          $bubbleable_metadata,
        );
        break;

      default:
        $gallery_id = '';
    }

    return $gallery_id;
  }

}
