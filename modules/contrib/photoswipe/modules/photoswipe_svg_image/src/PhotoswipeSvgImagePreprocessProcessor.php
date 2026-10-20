<?php

namespace Drupal\photoswipe_svg_image;

use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\photoswipe\ImageDTO;
use Drupal\photoswipe\PhotoswipePreprocessProcessor;

/**
 * Preprocesses Photoswipe images for image fields using the SVG Image module.
 *
 * Image styles cannot generate raster derivatives from an SVG source file,
 * so this processor bypasses image style handling for SVG files, while
 * leaving the normal image style pipeline untouched for any other file in
 * the same field.
 */
class PhotoswipeSvgImagePreprocessProcessor extends PhotoswipePreprocessProcessor {

  /**
   * {@inheritdoc}
   */
  public function preprocess(array &$variables) {
    $this->imageDTO = ImageDTO::createFromVariables($variables);
    $this->ensureSvgDimensions();
    $image = $this->getRenderableImage($variables);
    $variables['image'] = $image;
    $variables['path'] = $this->getPath();
    $variables['attributes']['class'][] = 'photoswipe';
    $variables['attributes']['data-pswp-width'] = $this->imageDTO->getWidth();
    $variables['attributes']['data-pswp-height'] = $this->imageDTO->getHeight();
    if (isset($image['#style_name']) && $image['#style_name'] === 'hide') {
      unset($variables['image']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getPath() {
    $uri = $this->imageDTO->getUri();
    if ($uri === NULL) {
      return NULL;
    }
    $file = $this->loadFileByUri($uri);
    if ($file && svg_image_is_file_svg($file)) {
      // Image styles cannot produce a raster derivative from SVG, so link
      // to a sanitized copy of the original rather than the stored file
      // itself, which may contain untrusted markup from the upload.
      return Url::fromRoute('photoswipe_svg_image.sanitized_svg', ['file' => $file->id()])
        ->setAbsolute()
        ->toString();
    }
    return parent::getPath();
  }

  /**
   * Resolves SVG dimensions when the field item has none.
   *
   * Image field items only carry width/height metadata when the active
   * image toolkit was able to read it on upload. The GD toolkit cannot read
   * SVG files, so items uploaded through the SVG Image module usually have
   * empty width/height. Fall back to the dimensions declared in the SVG
   * source itself, using the same logic the SVG Image module already uses
   * for its own previews.
   */
  protected function ensureSvgDimensions(): void {
    $dimensions = $this->imageDTO->getDimensions();
    if (!empty($dimensions[ImageDTO::WIDTH]) && !empty($dimensions[ImageDTO::HEIGHT])) {
      return;
    }
    $uri = $this->imageDTO->getUri();
    if ($uri === NULL) {
      return;
    }
    $file = $this->loadFileByUri($uri);
    if (!$file || !svg_image_is_file_svg($file)) {
      return;
    }
    $svgDimensions = svg_image_get_image_file_dimensions($file);
    $this->imageDTO->setDimensions([
      ImageDTO::WIDTH => $svgDimensions['width'],
      ImageDTO::HEIGHT => $svgDimensions['height'],
    ]);
  }

  /**
   * Loads the file entity for a given URI.
   *
   * @param string $uri
   *   The file URI.
   *
   * @return \Drupal\file\FileInterface|null
   *   The file entity, or NULL if none could be found.
   */
  protected function loadFileByUri(string $uri): ?FileInterface {
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties(['uri' => $uri]);
    return $files ? reset($files) : NULL;
  }

}
