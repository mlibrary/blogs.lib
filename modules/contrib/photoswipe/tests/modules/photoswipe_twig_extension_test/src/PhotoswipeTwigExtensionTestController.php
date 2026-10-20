<?php

namespace Drupal\photoswipe_twig_extension_test;

/**
 * Controller routines for Twig extension test routes.
 */
class PhotoswipeTwigExtensionTestController {

  /**
   * Menu callback for testing Twig functions in a Twig template.
   */
  public function renderTestTemplate() {
    return [
      '#theme' => 'photoswipe_twig_extension_test_function',
    ];
  }

  /**
   * Menu callback for testing Twig functions in a Twig template.
   */
  public function renderTestTemplateOptionsOverridden() {
    return [
      '#theme' => 'photoswipe_twig_extension_test_function_options_overridden',
    ];
  }

}
