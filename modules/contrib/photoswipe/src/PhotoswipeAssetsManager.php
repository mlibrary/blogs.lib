<?php

namespace Drupal\photoswipe;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManager;

/**
 * Photoswipe asset manager.
 */
class PhotoswipeAssetsManager implements PhotoswipeAssetsManagerInterface {

  /**
   * The minimum PhotoSwipe version we support.
   *
   * @var string
   */
  public $photoswipeMinPluginVersion = '5.4.2';

  /**
   * Photoswipe config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $themeManager;

  /**
   * Creates a \Drupal\photoswipe\PhotoswipeAssetsManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManager $themeManager
   *   The theme manager.
   */
  public function __construct(ConfigFactoryInterface $config, ModuleHandlerInterface $module_handler, ThemeManager $themeManager) {
    $this->config = $config->get('photoswipe.settings');
    $this->moduleHandler = $module_handler;
    $this->themeManager = $themeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$element, array $optionsOverride = []) {
    // Add the library of Photoswipe library and init:
    $element['#attached']['library'][] = 'photoswipe/photoswipe.init';

    // Add photoswipe js settings.
    $options = $this->config->get('options');

    if (!empty($optionsOverride)) {
      $options = array_merge($options, $optionsOverride);
    }
    // Allow other modules to alter / extend the options to pass to photoswipe
    // JavaScript.
    // Alter implementations may append cache tags when they read additional
    // config that affects the options.
    $cacheTags = ['config:photoswipe.settings'];
    $this->moduleHandler->alter('photoswipe_js_options', $options, $cacheTags);
    $this->themeManager->alter('photoswipe_js_options', $options, $cacheTags);
    $element['#attached']['drupalSettings']['photoswipe']['options'] = $options;
    $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'] ?? [], $cacheTags);

  }

}
