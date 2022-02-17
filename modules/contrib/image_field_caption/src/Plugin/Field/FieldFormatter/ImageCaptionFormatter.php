<?php

/**
 * @file
 * Contains \Drupal\image_field_caption\Plugin\Field\FieldFormatter\ImageCaptionFormatter.
 */

namespace Drupal\image_field_caption\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;

/**
 * Plugin implementation of the 'image_caption' formatter.
 *
 * @FieldFormatter(
 *   id = "image_caption",
 *   label = @Translation("Image with caption"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class ImageCaptionFormatter extends ImageFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as $delta => $element) {
      // Set a new theme callback function for the image caption formatter.
      $elements[$delta]['#theme'] = 'image_caption_formatter';
    }
    return $elements;
  }
}
