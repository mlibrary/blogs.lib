<?php

namespace Drupal\photoswipe;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Preprocess photoswipe images.
 */
class PhotoswipePreprocessProcessor implements ContainerInjectionInterface {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Image DTO.
   *
   * @var \Drupal\photoswipe\ImageDTO
   */
  protected $imageDTO;

  /**
   * File url generator object.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The logger object.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs new PhotoswipePreprocessProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity manager.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   File url generator object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileUrlGeneratorInterface $fileUrlGenerator,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->logger = $loggerFactory->get('photoswipe');
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_url_generator'),
      $container->get('logger.factory'),
    );
  }

  /**
   * Preprocess image.
   *
   * @param array $variables
   *   Variables.
   */
  public function preprocess(array &$variables) {
    $this->imageDTO = ImageDTO::createFromVariables($variables);
    $image = $this->getRenderableImage($variables);
    $variables['image'] = $image;
    $variables['path'] = $this->getPath();
    $variables['attributes']['class'][] = 'photoswipe';
    $variables['attributes']['data-pswp-width'] = $this->imageDTO->getWidth();
    $variables['attributes']['data-pswp-height'] = $this->imageDTO->getHeight();
    if (isset($image['#style_name']) && $image['#style_name'] === 'hide') {
      // If hidden is selected simply unset the entire image, so that we only
      // show the photoswipe anchor tag:
      unset($variables['image']);
    }
  }

  /**
   * Builds a renderable array for the given image.
   *
   * @param array $variables
   *   An associative array containing image variables.
   *
   * @return array
   *   Renderable array containing the image.
   */
  protected function getRenderableImage(array $variables) {

    // We need to merge the item attributes here, instead of doing it in
    // "preprocess", as responsive images use the "responsive_image" key instead
    // of "image":
    $itemAttributes = $variables['item_attributes'];
    // $this->imageDTO->getItem()->_attributes can be potentially NULL:
    if ($this->imageDTO->getItem()->_attributes) {
      $itemAttributes = array_merge($this->imageDTO->getItem()->_attributes, $itemAttributes);
    }

    $image = [
      '#theme' => 'image_style',
      '#uri' => $this->imageDTO->getUri(),
      '#alt' => $this->imageDTO->getAlt(),
      '#title' => $this->imageDTO->getTitle(),
      '#width' => $this->imageDTO->getWidth(),
      '#height' => $this->imageDTO->getHeight(),
      '#attributes' => $itemAttributes,
      '#style_name' => $this->imageDTO->getSettings()['photoswipe_thumbnail_style'],
    ];

    if (isset($variables['delta']) && $variables['delta'] === 0 && !empty($this->imageDTO->getSettings()['photoswipe_thumbnail_style_first'])) {
      $image['#style_name'] = $this->imageDTO->getSettings()['photoswipe_thumbnail_style_first'];
    }

    // Render as a standard image if an image style is not given.
    if (empty($image['#style_name']) || $image['#style_name'] === 'hide') {
      $image['#theme'] = 'image';
    }

    return $image;
  }

  /**
   * Set image path.
   */
  protected function getPath() {
    if ($this->imageDTO->getUri() === NULL) {
      $this->logger->warning('Can not apply photoswipe on image. The referenced file does not exist.');
      return NULL;
    }
    $dimensions = $this->imageDTO->getDimensions();
    // Create the path to the image that will show in Photoswipe.
    if (($style_name = $this->imageDTO->getSettings()['photoswipe_image_style']) && !empty($dimensions)) {
      // Load the image style.
      $style = $this->entityTypeManager->getStorage('image_style')->load($style_name);
      if (!$style) {
        $this->logger->warning('Can not apply photoswipe on image. Image style "@style" does not exist.', ['@style' => $style_name]);
        return NULL;
      }

      /** @var \Drupal\image\ImageStyleInterface $style */
      // Set the dimensions:
      $style->transformDimensions($dimensions, $this->imageDTO->getUri());
      $this->imageDTO->setDimensions($dimensions);

      // Fetch the Image style path from the Image URI.
      return $style->buildUrl($this->imageDTO->getUri());
    }
    else {
      return $this->fileUrlGenerator->generateAbsoluteString($this->imageDTO->getUri());
    }
  }

}
