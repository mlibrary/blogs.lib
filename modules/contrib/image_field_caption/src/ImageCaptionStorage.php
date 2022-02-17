<?php

/**
 * @file
 * Contains \Drupal\image_field_caption\ImageCaptionStorage.
 */

namespace Drupal\image_field_caption;

use Drupal\Core\Database\Connection;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidator;

/**
 * Storage controller class for image captions.
 *
 * @todo Use array with key/value as argument instead several arguments.
 * @todo The methods isCaption() and deleteCaption() must manage the revisions by itself, instead to have two differents methods.
 */
class ImageCaptionStorage {

  /**
   * The Cache Backend.
   *
   * @var CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * The Cache Tags Invalidator.
   *
   * @var CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The name of the data table.
   *
   * @var string
   */
  protected $tableData = 'image_field_caption';

  /**
   * The name of the revision table.
   *
   * @var string
   */
  protected $tableRevision = 'image_field_caption_revision';

  /**
   * AbstractService constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   The Cache Backend.
   * @param \Drupal\Core\Database\Connection $database
   *   The Database.
   */
  public function __construct(
        CacheBackendInterface $cacheBackend,
        CacheTagsInvalidator $cacheTagsInvalidator,
        Connection $database
    ) {
    $this->cacheBackend = $cacheBackend;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
    $this->database = $database;
  }

  /**
   * Check if a caption is already defined for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field,
   *   like 'field_image' or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param int $revision_id
   *   The revision id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   * @param int $delta
   *   The delta of the image field.
   *
   * @return bool
   *   TRUE if a caption exists or FALSE if not.
   */
  public function isCaption($entity_type, $bundle, $field_name, $entity_id, $revision_id, $language, $delta) {
    return (!empty(self::getCaption($entity_type, $bundle, $field_name, $entity_id, $revision_id, $language, $delta))) ? TRUE : FALSE;
  }

  /**
   * Get a caption from the database for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field,
   *   like 'field_image' or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param int $revision_id
   *   The revision id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   * @param int $delta
   *   The delta of the image field.
   *
   * @return array
   *   A caption array
   *   - caption: The caption text.
   *   - caption_format: The caption format.
   *   or an empty array, if no value found.
   */
  public function getCaption($entity_type, $bundle, $field_name, $entity_id, $revision_id, $language, $delta) {
    $captions = &drupal_static(__FUNCTION__);

    $cacheKey = $this->getCacheKey($entity_type, $entity_id, $revision_id, $language, $field_name, $delta);

    if (isset($captions[$cacheKey])) {
      $caption = $captions[$cacheKey];
    }
    elseif ($cached = $this->cacheBackend->get($cacheKey)) {
      $caption = $cached->data;
    }
    else {
      // Query.
      $query = $this->database->select($this->tableData, 'ifc');
      $result = $query
              ->fields('ifc', array('caption', 'caption_format'))
              ->condition('entity_type', $entity_type, '=')
              ->condition('bundle', $bundle, '=')
              ->condition('field_name', $field_name, '=')
              ->condition('entity_id', $entity_id, '=')
              ->condition('revision_id', $revision_id, '=')
              ->condition('language', $language, '=')
              ->condition('delta', $delta, '=')
              ->execute()
              ->fetchAssoc();

      // Caption array.
      $caption = array();
      if (!empty($result)) {
        $caption = $result;
      }

      // Let the cache depends on the entity.
      // TODO: Use getCacheTags() to get the default list.
      $this->cacheBackend->set(
            $cacheKey,
            $caption,
            Cache::PERMANENT,
            [
              $field_name,
              'image_field_caption',
            ]
        );
    }

    return $caption;
  }

  /**
   * Insert a caption into the database for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field,
   *   like 'field_image' or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param int $revision_id
   *   The revision id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   * @param int $delta
   *   The delta of the image field.
   * @param string $caption
   *   The caption text.
   * @param string $caption_format
   *   The text format of the caption.
   */
  public function insertCaption($entity_type, $bundle, $field_name, $entity_id, $revision_id, $language, $delta, $caption, $caption_format) {

    $query = $this->database->insert($this->tableData);
    $query
          ->fields(array(
            'entity_type' => $entity_type,
            'bundle' => $bundle,
            'field_name' => $field_name,
            'entity_id' => $entity_id,
            'revision_id' => $revision_id,
            'language' => $language,
            'delta' => $delta,
            'caption' => $caption,
            'caption_format' => $caption_format,
          ))
      ->execute();
    $this->clearCache($field_name);
  }

  /**
   * Insert a caption revision into the database for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field,
   *   like 'field_image' or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param int $revision_id
   *   The revision id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   * @param int $delta
   *   The delta of the image field.
   * @param string $caption
   *   The caption text.
   * @param string $caption_format
   *   The text format of the caption.
   */
  public function insertCaptionRevision($entity_type, $bundle, $field_name, $entity_id, $revision_id, $language, $delta, $caption, $caption_format) {
    $query = $this->database->insert($this->tableRevision);
    $query
          ->fields(array(
            'entity_type' => $entity_type,
            'bundle' => $bundle,
            'field_name' => $field_name,
            'entity_id' => $entity_id,
            'revision_id' => $revision_id,
            'language' => $language,
            'delta' => $delta,
            'caption' => $caption,
            'caption_format' => $caption_format,
          ))
      ->execute();
    $this->clearCache($field_name);
  }

  /**
   * Delete a caption from the database for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field, like
   *   'field_image' or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   */
  public function deleteCaption($entity_type, $bundle, $field_name, $entity_id, $language) {
    $query = $this->database->delete($this->tableData);
    $query
          ->condition('entity_type', $entity_type, '=')
          ->condition('bundle', $bundle, '=')
          ->condition('field_name', $field_name, '=')
          ->condition('entity_id', $entity_id, '=')
          ->condition('language', $language, '=')
          ->execute();
    $this->clearCache($field_name);
    // @todo Try to return the count of the affected rows.
  }

  /**
   * Delete a caption revision from the database for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field, like
   *   'field_image' or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param int $revision_id
   *   The revision id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   */
  public function deleteCaptionRevision($entity_type, $bundle, $field_name, $entity_id, $revision_id, $language) {
    $query = $this->database->delete($this->tableRevision);
    $query
      ->condition('entity_type', $entity_type, '=')
      ->condition('bundle', $bundle, '=')
      ->condition('field_name', $field_name, '=')
      ->condition('entity_id', $entity_id, '=')
      ->condition('revision_id', $revision_id, '=')
      ->condition('language', $language, '=')
      ->execute();
    $this->clearCache($field_name);
    // @todo Try to return the count of the affected rows.
  }

  /**
   * Delete all captions revisions for the specified arguments.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param string $bundle
   *   The bundle, like 'article' or 'news'.
   * @param string $field_name
   *   The field name of the image field, like 'field_image'
   *   or 'field_article_image'.
   * @param int $entity_id
   *   The entity id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   */
  public function deleteCaptionRevisions($entity_type, $bundle, $field_name, $entity_id, $language) {
    $query = $this->database->delete($this->tableRevision);
    $query
      ->condition('entity_type', $entity_type, '=')
      ->condition('bundle', $bundle, '=')
      ->condition('field_name', $field_name, '=')
      ->condition('entity_id', $entity_id, '=')
      ->condition('language', $language, '=')
      ->execute();
    $this->clearCache($field_name);
    // @todo Try to return the count of the affected rows.
  }

  /**
   * Delete all captions revisions for a specific revision id.
   *
   * @param int $revision_id
   *   The revision id.
   */
  public function deleteCaptionRevisionsByRevisionId($revision_id) {
    $query = $this->database->delete($this->tableRevision);
    $query
      ->condition('revision_id', $revision_id, '=')
      ->execute();
  }

  /**
   * Clears the cache for a certain field name.
   *
   * @param string $field_name
   *   The field name of the image field, like 'field_image'
   *   or 'field_article_image'.
   */
  public function clearCache($field_name) {
    $this->cacheTagsInvalidator->invalidateTags([
      $field_name,
      'image_field_caption',
    ]);
  }

  /**
   * Constructs the cache key.
   *
   * @param string $entity_type
   *   The entity type, like 'node' or 'comment'.
   * @param int $entity_id
   *   The entity id.
   * @param int $revision_id
   *   The revision id.
   * @param string $language
   *   The language key, like 'en' or 'fr'.
   * @param string $field_name
   *   The field name of the image field, like 'field_image'
   *   or 'field_article_image'.
   * @param int $delta
   *   The delta of the image field.
   */
  public function getCacheKey($entity_type, $entity_id, $revision_id, $language, $field_name, $delta) {
    return implode(
        ":",
        [
          'caption',
          $entity_type,
          $entity_id,
          $revision_id,
          $language,
          $field_name,
          $delta,
        ]
    );
  }
  
  public function list($key = 'entity_type') {
      $list = &drupal_static(__FUNCTION__);
    
      if (!isset($list[$key])) {
          // Query.
          $query = $this->database->select($this->tableData, 'ifc');
          $result = $query
              ->fields('ifc', array($key))
              ->distinct()
              ->execute()
              ->fetchAll();
    
          $list[$key] = [];
          foreach ($result as $row) {
              $list[$key][] = $row->{$key};
          }
      }
      
      return $list[$key];
  }

}
