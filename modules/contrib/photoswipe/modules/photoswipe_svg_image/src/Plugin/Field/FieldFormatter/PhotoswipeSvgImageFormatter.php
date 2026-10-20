<?php

namespace Drupal\photoswipe_svg_image\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\photoswipe\Plugin\Field\FieldFormatter\PhotoswipeFieldFormatter;

/**
 * Plugin implementation of the 'photoswipe_svg_image_formatter' formatter.
 *
 * Behaves exactly like the base Photoswipe formatter, but renders through
 * the 'photoswipe_svg_image_formatter' theme hook so that SVG files
 * bypass image style derivative generation.
 *
 * @FieldFormatter(
 *   id = "photoswipe_svg_image_formatter",
 *   label = @Translation("Photoswipe (SVG Image)"),
 *   field_types = {
 *     "entity_reference",
 *     "image"
 *   }
 * )
 */
class PhotoswipeSvgImageFormatter extends PhotoswipeFieldFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    foreach ($elements as &$element) {
      if (isset($element['#theme']) && $element['#theme'] === 'photoswipe_image_formatter') {
        $element['#theme'] = 'photoswipe_svg_image_formatter';
      }
    }
    return $elements;
  }

}
