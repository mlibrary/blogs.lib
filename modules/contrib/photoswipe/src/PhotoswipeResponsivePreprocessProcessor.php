<?php

namespace Drupal\photoswipe;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Preprocess photoswipe images.
 */
class PhotoswipeResponsivePreprocessProcessor extends PhotoswipePreprocessProcessor {

  /**
   * {@inheritDoc}
   */
  protected function getRenderableImage($variables) {
    $item = $this->imageDTO->getItem();
    $settings = $this->imageDTO->getSettings();
    $image_style_store = $this->entityTypeManager->getStorage('image_style');
    $resp_image_store = $this->entityTypeManager->getStorage('responsive_image_style');

    // Resolve the effective thumbnail style up front, so that the style lookup,
    // caching, etc. respect the first image override:
    $thumbnail_style = $settings['photoswipe_thumbnail_style'];
    // For the first item, use "photoswipe_thumbnail_style_first" for the
    // thumbnail style:
    if (isset($variables['delta']) && $variables['delta'] === 0 && !empty($settings['photoswipe_thumbnail_style_first'])) {
      $thumbnail_style = $settings['photoswipe_thumbnail_style_first'];
    }
    $responsive_image_style = $resp_image_store->load($thumbnail_style);

    $cache_tags = [];
    $image_styles_to_load = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $image_style_store->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    // We need to merge the item attributes here, instead of doing it in
    // "preprocess", as responsive images use the "responsive_image" key instead
    // of "image":
    $itemAttributes = $variables['item_attributes'];
    // Check if this is not NULL, just in case, before merging:
    if ($item->_attributes) {
      $itemAttributes = array_merge($item->_attributes, $variables['item_attributes']);
    }

    $image = [
      '#theme' => 'responsive_image_formatter',
      '#item' => $item,
      '#item_attributes' => $itemAttributes,
      '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
      '#cache' => [
        'tags' => $cache_tags,
      ],
      '#style_name' => $thumbnail_style,
    ];

    $meta_a = CacheableMetadata::createFromRenderArray($image);
    $meta_b = CacheableMetadata::createFromObject($item->getEntity());
    $meta_a->merge($meta_b)->applyTo($image);

    // Render as a standard image if an image style is not given or "hide".
    if ($image['#style_name'] === 'hide') {
      $image['#theme'] = 'image';
    }

    return $image;
  }

}
