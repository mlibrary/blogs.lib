<?php

namespace Drupal\file_entity\Normalizer;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\FileInterface;
use Drupal\hal\Normalizer\ContentEntityNormalizer;

/**
 * Normalizer for File entity.
 */
class FileEntityNormalizer extends ContentEntityNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\file\FileInterface';

  /**
   * {@inheritdoc}
   */
  public function getSupportedTypes(?string $format): array {
    return [
      FileInterface::class => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []): array|string|int|float|bool|\ArrayObject|NULL {
    $data = parent::normalize($entity, $format, $context);
    if (!isset($context['included_fields']) || in_array('data', $context['included_fields'])) {
      $file_uri = $entity->getFileUri();

      // Check if the file exists before attempting to read it.
      if (file_exists($file_uri)) {
        // Save base64-encoded file contents to the "data" property.
        $file_data = base64_encode(file_get_contents($file_uri));
          $data += array(
          'data' => array(array('value' => $file_data)),
        );
      }
      else {
        // Save null to the "data" property.
        $data += array(
          'data' => array(array('value' => NULL)),
        );
      }
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = array()): mixed {
    // Avoid 'data' being treated as a field.
    $file_data = $data['data'][0]['value'];
    unset($data['data']);
    // Decode and save to file.
    $file_contents = base64_decode($file_data);
    $entity = parent::denormalize($data, $class, $format, $context);
    $dirname = \Drupal::service('file_system')->dirname($entity->getFileUri());
    \Drupal::service('file_system')->prepareDirectory($dirname, FileSystemInterface::CREATE_DIRECTORY);
    if ($uri = \Drupal::service('file_system')->saveData($file_contents, $entity->getFileUri())) {
      $entity->setFileUri($uri);
    }
    else {
      throw new \RuntimeException(new FormattableMarkup('Failed to write @filename.', array('@filename' => $entity->getFilename())));
    }
    return $entity;
  }
}
