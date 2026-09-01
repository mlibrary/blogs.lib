<?php

namespace Drupal\glightbox;

use Drupal\Core\Asset\LibrariesDirectoryFileFinder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * An implementation of PageAttachmentInterface for the glightbox library.
 */
class GLightboxAttachment implements ElementAttachmentInterface {

  use StringTranslationTrait;

  /**
   * The service to determine if glightbox should be activated.
   *
   * @var \Drupal\glightbox\ActivationCheckInterface
   */
  protected $activation;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The glightbox settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * The library finder service.
   *
   * @var \Drupal\Core\Asset\LibrariesDirectoryFileFinder
   */
  protected $finder;

  /**
   * Create an instance of GLightboxAttachment.
   */
  public function __construct(
    ActivationCheckInterface $activation,
    ModuleHandlerInterface $module_handler,
    ConfigFactoryInterface $config,
    LibrariesDirectoryFileFinder $finder,
  ) {
    $this->activation = $activation;
    $this->moduleHandler = $module_handler;
    $this->settings = $config->get('glightbox.settings');
    $this->finder = $finder;
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return !InstallerKernel::installationAttempted() && $this->activation->isActive();
  }

  /**
   * {@inheritdoc}
   */
  public function attach(array &$page) {
    if ($this->settings->get('custom.activate')) {
      $js_settings = [
        'openEffect' => $this->settings->get('custom.open_effect') ?? 'zoom',
        'closeEffect' => $this->settings->get('custom.close_effect') ?? 'zoom',
        'slideEffect' => $this->settings->get('custom.slide_effect') ?? 'slide',
        'closeOnOutsideClick' => (bool) $this->settings->get('custom.close_on_outside_click'),
        'width' => $this->settings->get('custom.width') ?? '98%',
        'height' => $this->settings->get('custom.height') ?? '98%',
        'videosWidth' => $this->settings->get('custom.videos_width') ?? '',
        'moreText' => $this->settings->get('custom.more_text') ?? '',
        'descPosition' => $this->settings->get('custom.desc_position') ?? 'bottom',
        'loop' => (bool) $this->settings->get('custom.loop'),
        'zoomable' => (bool) $this->settings->get('custom.zoomable'),
        'draggable' => (bool) $this->settings->get('custom.draggable'),
        'preload' => (bool) $this->settings->get('custom.preload'),
        'autoplayVideos' => (bool) $this->settings->get('custom.autoplay_videos'),
        'autofocusVideos' => (bool) $this->settings->get('custom.autofocus_videos'),
      ];
    }
    else {
      $js_settings = [
        'width' => '98%',
        'height' => '98%',
      ];
    }

    // Add local plyr.io libraries if they're present.
    $path = $this->finder->find('plyr/plyr.js');
    if ($path) {
      $js_settings['plyr']['js'] = '/' . $path;
    }
    $path = $this->finder->find('plyr/plyr.css');
    if ($path) {
      $js_settings['plyr']['css'] = '/' . $path;
    }

    $js_settings['plyr']['enabled'] = $this->settings->get('plyr.enabled') ?? TRUE;
    $js_settings['plyr']['debug'] = $this->settings->get('plyr.debug') ?? FALSE;
    $js_settings['plyr']['controls'] = $this->settings->get('plyr.controls') ?? "['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen']";
    $js_settings['plyr']['settings'] = $this->settings->get('plyr.settings') ?? "['captions', 'quality', 'speed', 'loop']";
    $js_settings['plyr']['loadSprite'] = $this->settings->get('plyr.load_sprite') ?? TRUE;
    $js_settings['plyr']['iconUrl'] = $this->settings->get('plyr.icon_url') ?? NULL;
    $js_settings['plyr']['iconPrefix'] = $this->settings->get('plyr.icon_prefix') ?? 'plyr';
    $js_settings['plyr']['blankVideo'] = $this->settings->get('plyr.blank_video') ?? 'https://cdn.plyr.io/static/blank.mp4';
    $js_settings['plyr']['autoplay'] = $this->settings->get('plyr.autoplay') ?? FALSE;
    $js_settings['plyr']['autopause'] = $this->settings->get('plyr.autopause') ?? TRUE;
    $js_settings['plyr']['playsinline'] = $this->settings->get('plyr.playsinline') ?? TRUE;
    $js_settings['plyr']['seekTime'] = $this->settings->get('plyr.seek_time') ?? 10;
    $js_settings['plyr']['volume'] = $this->settings->get('plyr.volume') ?? 1;
    $js_settings['plyr']['muted'] = $this->settings->get('plyr.muted') ?? FALSE;
    $js_settings['plyr']['clickToPlay'] = $this->settings->get('plyr.click_to_play') ?? TRUE;
    $js_settings['plyr']['disableContextMenu'] = $this->settings->get('plyr.disable_context_menu') ?? TRUE;
    $js_settings['plyr']['hideControls'] = $this->settings->get('plyr.hide_controls') ?? TRUE;
    $js_settings['plyr']['resetOnEnd'] = $this->settings->get('plyr.reset_on_end') ?? FALSE;
    $js_settings['plyr']['displayDuration'] = $this->settings->get('plyr.display_duration') ?? TRUE;
    $js_settings['plyr']['invertTime'] = $this->settings->get('plyr.invert_time') ?? TRUE;
    $js_settings['plyr']['toggleInvert'] = $this->settings->get('plyr.toggle_invert') ?? TRUE;
    $js_settings['plyr']['ratio'] = $this->settings->get('plyr.ratio') ?? NULL;

    // Give other modules the possibility to override GLightbox settings.
    $this->moduleHandler->alter('glightbox_settings', $js_settings);

    // Add glightbox js settings.
    $page['#attached']['drupalSettings']['glightbox'] = $js_settings;

    // Add and initialise the GLightbox plugin.
    if ($this->settings->get('advanced.compression_type') == 'minified') {
      $page['#attached']['library'][] = 'glightbox/glightbox';
    }
    else {
      $page['#attached']['library'][] = 'glightbox/glightbox-dev';
    }

    $page['#attached']['library'][] = "glightbox/init";
  }

}
