<?php

/**
 * @file
 * API documentation for the glightbox module.
 */

/**
 * Allows to override GLightbox settings.
 *
 * Implements hook_glightbox_settings_alter().
 *
 * @param array $settings
 *   An associative array of GLightbox settings. See link below.
 *
 * @link
 *    https://github.com/biati-digital/glightbox/blob/master/README.md
 *    GLightbox documentation.
 * @endlink
 *   for the full list of supported parameters.
 *
 * @codingStandardsIgnoreStart
 */
function hook_glightbox_settings_alter(&$settings) {
  // @codingStandardsIgnoreEnd
  // Disable automatic downscaling of images to width/height size.
  $settings['scalePhotos'] = FALSE;

  // Use custom style plugin specifically for node/42.
  if (\Drupal::service('path.current')->getPath() == 'node/42') {
    $settings['scalePhotos'] = TRUE;
  }
}
