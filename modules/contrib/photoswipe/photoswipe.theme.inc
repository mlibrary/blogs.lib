<?php

/**
 * @file
 * Photoswipe theme preprocess functions.
 */

use Drupal\photoswipe\PhotoswipePreprocessProcessor;
use Drupal\photoswipe\PhotoswipeResponsivePreprocessProcessor;

/**
 * Prepares variables for a Photoswipe image field formatter.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - display_settings: optional image styles.
 *   - third_party_settings: optional third party settings.
 *
 * @ingroup themeable
 */
function template_preprocess_photoswipe_image_formatter(array &$variables) {
  \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(PhotoswipePreprocessProcessor::class)
    ->preprocess($variables);
}

/**
 * Prepares variables for a Photoswipe responsive image field formatter.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - display_settings: optional image styles.
 *   - third_party_settings: optional third party settings.
 *
 * @ingroup themeable
 */
function template_preprocess_photoswipe_responsive_image_formatter(array &$variables) {
  \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(PhotoswipeResponsivePreprocessProcessor::class)
    ->preprocess($variables);
}
