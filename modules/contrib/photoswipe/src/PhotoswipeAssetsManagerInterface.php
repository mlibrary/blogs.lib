<?php

namespace Drupal\photoswipe;

/**
 * Interface for photoswipe assets manager.
 */
interface PhotoswipeAssetsManagerInterface {

  /**
   * Attach photoswipe assets.
   *
   * @param array $element
   *   The render array to attach photoswipe assets to.
   * @param array $optionsOverride
   *   An array of photoswipe options to override the current global settings.
   */
  public function attach(array &$element, array $optionsOverride = []);

}
