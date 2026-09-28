<?php

namespace Drupal\glightbox\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * General configuration form for controlling the glightbox behaviour..
 */
class GLightboxSettingsForm extends ConfigFormBase {

  /**
   * The list of available modules.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * A state that represents the custom settings being enabled.
   */
  const STATE_CUSTOM_SETTINGS = 0;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Configuration Factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The list of available modules.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The library discovery service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_configmanager
   *   The typed config manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $moduleHandler,
                              ModuleExtensionList $extension_list_module,
                              LibraryDiscoveryInterface $libraryDiscovery,
                              TypedConfigManagerInterface $typed_configmanager) {
    parent::__construct($config_factory, $typed_configmanager);
    $this->moduleHandler = $moduleHandler;
    $this->extensionListModule = $extension_list_module;
    $this->libraryDiscovery = $libraryDiscovery;
    $this->typedConfigManager = $typed_configmanager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('extension.list.module'),
      $container->get('library.discovery'),
      $container->get('config.typed')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'glightbox_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['glightbox.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('glightbox.settings');
    $form = [];

    $form['glightbox_custom_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#open' => TRUE,
    ];
    $form['glightbox_custom_settings']['glightbox_custom_settings_activate'] = [
      '#type' => 'radios',
      '#title' => $this->t('Options'),
      '#options' => [0 => $this->t('Default'), 1 => $this->t('Custom')],
      '#default_value' => $config->get('custom.activate') ?? 0,
      '#description' => $this->t('Use the default or custom options for GLightbox.'),
    ];
    $form['glightbox_custom_settings']['glightbox_open_effect'] = [
      '#type' => 'radios',
      '#title' => $this->t('Open effect'),
      '#options' => [
        'zoom' => $this->t('Zoom'),
        'fade' => $this->t('Fade'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $config->get('custom.open_effect') ?? 'zoom',
      '#description' => $this->t('Name of the effect on lightbox open.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_close_effect'] = [
      '#type' => 'radios',
      '#title' => $this->t('Close effect'),
      '#options' => [
        'zoom' => $this->t('Zoom'),
        'fade' => $this->t('Fade'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $config->get('custom.close_effect') ?? 'zoom',
      '#description' => $this->t('Name of the effect on lightbox close.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_slide_effect'] = [
      '#type' => 'radios',
      '#title' => $this->t('Slide effect'),
      '#options' => [
        'slide' => $this->t('Slide'),
        'fade' => $this->t('Fade'),
        'zoom' => $this->t('Zoom'),
        'none' => $this->t('None'),
      ],
      '#default_value' => $config->get('custom.slide_effect') ?? 'slide',
      '#description' => $this->t('Name of the effect on slide change.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('width'),
      '#default_value' => $config->get('custom.width') ?? '98%',
      '#size' => 30,
      '#description' => $this->t('Set a width for loaded content. Example: "100%", 500, "500px".'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Height'),
      '#default_value' => $config->get('custom.height') ?? '98%',
      '#size' => 30,
      '#description' => $this->t('Set a height for loaded content. Example: "100%", 500, "500px".'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_videos_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Videos width'),
      '#default_value' => $config->get('custom.videos_width') ?? '',
      '#size' => 30,
      '#description' => $this->t('Default width for videos. Videos are responsive so height is not required. The width can be in px % or even vw for example, 500px, 90% or 100vw for full width videos'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_close_on_outside_click'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Overlay close'),
      '#default_value' => $config->get('custom.close_on_outside_click') ?? 1,
      '#description' => $this->t('Enable closing GLightbox by clicking on the background overlay.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_more_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('See More text'),
      '#default_value' => $config->get('custom.glightbox_more_text') ?? '',
      '#size' => 30,
      '#description' => $this->t('More text for descriptions on mobile devices.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_more_length'] = [
      '#type' => 'select',
      '#title' => $this->t('More text threshold'),
      '#options' => $this->optionsRange(0, 120, 5),
      '#default_value' => $config->get('custom.more_length') ?? 60,
      '#description' => $this->t('Number of characters to display on the description before adding the moreText link (only for mobiles), if 0 it will display the entire description.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_desc_position'] = [
      '#type' => 'radios',
      '#title' => $this->t('Description position'),
      '#options' => [
        'bottom' => $this->t('Bottom'),
        'top' => $this->t('Top'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $config->get('custom.desc_position') ?? 'bottom',
      '#description' => $this->t('Global position for slides description, you can define a specific position on each slide (bottom, top, left, right).'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop'),
      '#default_value' => $config->get('custom.loop') ?? 0,
      '#description' => $this->t('Loop slides on end.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_zoomable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Zoomable'),
      '#default_value' => $config->get('custom.zoomable') ?? 1,
      '#description' => $this->t('Enable or disable zoomable images.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_draggable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Draggable') ?? 1,
      '#default_value' => $config->get('custom.draggable'),
      '#description' => $this->t('Enable or disable mouse drag to go prev and next slide (only images and inline content).'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_preload'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Preload'),
      '#default_value' => $config->get('custom.preload') ?? 1,
      '#description' => $this->t('Enable or disable preloading.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_autoplay_videos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay videos') ?? 1,
      '#default_value' => $config->get('custom.autoplay_videos'),
      '#description' => $this->t('Autoplay videos on open.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];
    $form['glightbox_custom_settings']['glightbox_autofocus_videos'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autofocus videos') ?? 0,
      '#default_value' => $config->get('custom.autofocus_videos'),
      '#description' => $this->t('If true video will be focused on play to allow keyboard sortcuts for the player, this will deactivate prev and next arrows to change slide so use it only if you know what you are doing.'),
      '#states' => $this->getState(static::STATE_CUSTOM_SETTINGS),
    ];

    $form['glightbox_advanced_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
    ];
    $form['glightbox_advanced_settings']['glightbox_unique_token'] = [
      '#type' => 'radios',
      '#title' => $this->t('Unique per-request gallery token'),
      '#options' => [1 => $this->t('On'), 0 => $this->t('Off')],
      '#default_value' => $config->get('advanced.unique_token') ?? 0,
      '#description' => $this->t('If On, GLightbox will add a unique per-request token to the gallery id to avoid images being added manually to galleries. The token was added as a security fix but some see the old behavoiur as an feature and this settings makes it possible to remove the token.'),
    ];
    $form['glightbox_advanced_settings']['glightbox_compression_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Choose GLightbox compression level'),
      '#options' => [
        'minified' => $this->t('Production (Minified)'),
        'source' => $this->t('Development (Uncompressed Code)'),
      ],
      '#default_value' => $config->get('advanced.compression_type') ?? 'minified',
    ];

    // Video player plyr.io settings.
    $form['glightbox_plyr_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Plyr settings'),
    ];
    $form['glightbox_plyr_settings']['plyr_plugin_description'] = [
      '#markup' => 'GLightbox includes "Plyr" the best player out there, you can pass any Plyr option to the player, view all available options here Plyr options. GLightbox will only inject the player library if required and only when the lightbox is opened.'
    ];

    $form['glightbox_plyr_settings']['plyr_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $config->get('plyr.enabled') ?? 1,
      '#description' => $this->t('Completely disable Plyr. This would allow you to do a User Agent check or similar to programmatically enable or disable Plyr for a certain UA. Example in plyr.io documentation.'),
    ];

    $form['glightbox_plyr_settings']['plyr_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#default_value' => $config->get('plyr.debug') ?? 0,
      '#description' => $this->t('Display debugging information in the console.'),
    ];

    $form['glightbox_plyr_settings']['plyr_controls'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Controls'),
      '#default_value' => $config->get('plyr.controls') ?? "['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'pip', 'airplay', 'fullscreen']",
      '#size' => 30,
      '#description' => $this->t('If a function is passed, it is assumed your method will return either an element or HTML string for the controls. Three arguments will be passed to your function; id (the unique id for the player), seektime (the seektime step in seconds), and title (the media title). See CONTROLS.md for more info on how the html needs to be structured.'),
    ];

    $form['glightbox_plyr_settings']['plyr_settings'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Settings'),
      '#default_value' => $config->get('plyr.settings') ?? "['captions', 'quality', 'speed', 'loop']",
      '#size' => 30,
      '#description' => $this->t('If the default controls are used, you can specify which settings to show in the menu.'),
    ];

    $form['glightbox_plyr_settings']['plyr_load_sprite'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load Sprite'),
      '#default_value' => $config->get('plyr.load_sprite') ?? 1,
      '#description' => $this->t('Load the SVG sprite specified as the iconUrl option (if a URL). If false, it is assumed you are handling sprite loading yourself.'),
    ];

    $form['glightbox_plyr_settings']['plyr_icon_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon URL'),
      '#default_value' => $config->get('plyr.icon_url') ?? '',
      '#size' => 30,
      '#description' => $this->t('Specify a URL or path to the SVG sprite. See the SVG section for more info.'),
    ];

    $form['glightbox_plyr_settings']['plyr_icon_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon Prefix'),
      '#default_value' => $config->get('plyr.icon_prefix') ?? 'plyr',
      '#size' => 30,
      '#description' => $this->t('Specify the id prefix for the icons used in the default controls (e.g. "plyr-play" would be "plyr"). This is to prevent clashes if you\'re using your own SVG sprite but with the default controls. Most people can ignore this option.'),
    ];

    $form['glightbox_plyr_settings']['plyr_blank_video'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Blank Video'),
      '#default_value' => $config->get('plyr.blank_video') ?? 'https://cdn.plyr.io/static/blank.mp4',
      '#size' => 30,
      '#description' => $this->t('Specify a URL or path to a blank video file used to properly cancel network requests.'),
    ];

    $form['glightbox_plyr_settings']['plyr_autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autoplay'),
      '#default_value' => $config->get('plyr.autoplay') ?? 0,
      '#description' => $this->t('Autoplay the media on load. If the autoplay attribute is present on a video or audio element, this will be automatically set to true.'),
    ];

    $form['glightbox_plyr_settings']['plyr_autopause'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autopause'),
      '#default_value' => $config->get('plyr.autopause') ?? 1,
      '#description' => $this->t('Only allow one player playing at once.'),
    ];

    $form['glightbox_plyr_settings']['plyr_playsinline'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Plays inline'),
      '#default_value' => $config->get('plyr.playsinline') ?? 1,
      '#description' => $this->t('Allow inline playback on iOS. Note this has no effect on iPadOS.'),
    ];

    $form['glightbox_plyr_settings']['plyr_seek_time'] = [
      '#type' => 'number',
      '#step' => '1',
      '#min' => '1',
      '#size' => 3,
      '#title' => $this->t('Seek time'),
      '#default_value' => $config->get('plyr.seek_time') ?? 10,
      '#description' => $this->t('The time, in seconds, to seek when a user hits fast forward or rewind.'),
    ];

    $form['glightbox_plyr_settings']['plyr_volume'] = [
      '#type' => 'number',
      '#step' => '0.01',
      '#min' => '0',
      '#max' => '1',
      '#size' => 3,
      '#title' => $this->t('Volume'),
      '#default_value' => $config->get('plyr.volume') ?? 1,
      '#description' => $this->t('A number, between 0 and 1, representing the initial volume of the player.'),
    ];

    $form['glightbox_plyr_settings']['plyr_muted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Muted'),
      '#default_value' => $config->get('plyr.muted') ?? 0,
      '#description' => $this->t('Whether to start playback muted. If the muted attribute is present on a video or audio element, this will be automatically set to true.'),
    ];

    $form['glightbox_plyr_settings']['plyr_click_to_play'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Click to play'),
      '#default_value' => $config->get('plyr.click_to_play') ?? 1,
      '#description' => $this->t('Click (or tap) of the video container will toggle play/pause.'),
    ];

    $form['glightbox_plyr_settings']['plyr_disable_context_menu'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable context menu'),
      '#default_value' => $config->get('plyr.disable_context_menu') ?? 1,
      '#description' => $this->t('Disable right click menu on video to help as very primitive obfuscation to prevent downloads of content.'),
    ];

    $form['glightbox_plyr_settings']['plyr_hide_controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide controls'),
      '#default_value' => $config->get('plyr.hide_controls') ?? 1,
      '#description' => $this->t('Hide video controls automatically after 2s of no mouse or focus movement, on control element blur (tab out), on playback start or entering fullscreen. As soon as the mouse is moved, a control element is focused or playback is paused, the controls reappear instantly.'),
    ];

    $form['glightbox_plyr_settings']['plyr_reset_on_end'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset on end'),
      '#default_value' => $config->get('plyr.reset_on_end') ?? 0,
      '#description' => $this->t('Reset the playback to the start once playback is complete.'),
    ];

    $form['glightbox_plyr_settings']['plyr_display_duration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display duration'),
      '#default_value' => $config->get('plyr.display_duration') ?? 1,
      '#description' => $this->t('Displays the duration of the media on the "metadataloaded" event (on startup) in the current time display. This will only work if the preload attribute is not set to none (or is not set at all) and you choose not to display the duration (see controls option).'),
    ];

    $form['glightbox_plyr_settings']['plyr_invert_time'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Invert time'),
      '#default_value' => $config->get('plyr.invert_time') ?? 1,
      '#description' => $this->t('Display the current time as a countdown rather than an incremental counter.'),
    ];

    $form['glightbox_plyr_settings']['plyr_toggle_invert'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Toggle invert'),
      '#default_value' => $config->get('plyr.toggle_invert') ?? 1,
      '#description' => $this->t('Allow users to click to toggle the above.'),
    ];

    $form['glightbox_plyr_settings']['plyr_ratio'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ratio'),
      '#default_value' => $config->get('plyr.ratio') ?? '',
      '#size' => 30,
      '#description' => $this->t('Force an aspect ratio for all videos. The format is \'w:h\' - e.g. \'16:9\' or \'4:3\'. If this is not specified then the default for HTML5 and Vimeo is to use the native resolution of the video. As dimensions are not available from YouTube via SDK, 16:9 is forced as a sensible default.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->configFactory->getEditable('glightbox.settings');

    $config
      ->set('custom.activate', $form_state->getValue('glightbox_custom_settings_activate'))
      ->set('custom.open_effect', $form_state->getValue('glightbox_open_effect'))
      ->set('custom.close_effect', $form_state->getValue('glightbox_close_effect'))
      ->set('custom.slide_effect', $form_state->getValue('glightbox_slide_effect'))
      ->set('custom.close_on_outside_click', $form_state->getValue('glightbox_close_on_outside_click'))
      ->set('custom.width', $form_state->getValue('glightbox_width'))
      ->set('custom.height', $form_state->getValue('glightbox_height'))
      ->set('custom.videos_width', $form_state->getValue('glightbox_videos_width'))
      ->set('custom.more_text', $form_state->getValue('glightbox_more_text'))
      ->set('custom.more_length', $form_state->getValue('glightbox_more_length'))
      ->set('custom.desc_position', $form_state->getValue('glightbox_desc_position'))
      ->set('custom.autoplay_videos', $form_state->getValue('glightbox_autoplay_videos'))
      ->set('custom.loop', $form_state->getValue('glightbox_loop'))
      ->set('custom.zoomable', $form_state->getValue('glightbox_zoomable'))
      ->set('custom.draggable', $form_state->getValue('glightbox_draggable'))
      ->set('custom.preload', $form_state->getValue('glightbox_preload'))
      ->set('custom.autoplay_videos', $form_state->getValue('glightbox_autoplay_videos'))
      ->set('custom.autofocus_videos', $form_state->getValue('glightbox_autofocus_videos'))
      ->set('advanced.unique_token', $form_state->getValue('glightbox_unique_token'))
      ->set('advanced.compression_type', $form_state->getValue('glightbox_compression_type'))
      ->set('plyr.enabled', $form_state->getValue('plyr_enabled'))
      ->set('plyr.debug', $form_state->getValue('plyr_debug'))
      ->set('plyr.controls', $form_state->getValue('plyr_controls'))
      ->set('plyr.settings', $form_state->getValue('plyr_settings'))
      ->set('plyr.load_sprite', $form_state->getValue('plyr_load_sprite'))
      ->set('plyr.icon_url', $form_state->getValue('plyr_icon_url'))
      ->set('plyr.icon_prefix', $form_state->getValue('plyr_icon_prefix'))
      ->set('plyr.blank_video', $form_state->getValue('plyr_blank_video'))
      ->set('plyr.autoplay', $form_state->getValue('plyr_autoplay'))
      ->set('plyr.autopause', $form_state->getValue('plyr_autopause'))
      ->set('plyr.playsinline', $form_state->getValue('plyr_playsinline'))
      ->set('plyr.seek_time', $form_state->getValue('plyr_seek_time'))
      ->set('plyr.volume', $form_state->getValue('plyr_volume'))
      ->set('plyr.muted', $form_state->getValue('plyr_muted'))
      ->set('plyr.click_to_play', $form_state->getValue('plyr_click_to_play'))
      ->set('plyr.disable_context_menu', $form_state->getValue('plyr_disable_context_menu'))
      ->set('plyr.hide_controls', $form_state->getValue('plyr_hide_controls'))
      ->set('plyr.reset_on_end', $form_state->getValue('plyr_reset_on_end'))
      ->set('plyr.display_duration', $form_state->getValue('plyr_display_duration'))
      ->set('plyr.invert_time', $form_state->getValue('plyr_invert_time'))
      ->set('plyr.toggle_invert', $form_state->getValue('plyr_toggle_invert'))
      ->set('plyr.ratio', $form_state->getValue('plyr_ratio'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state) {
    $states = [
      static::STATE_CUSTOM_SETTINGS => [
        'visible' => [
          ':input[name="glightbox_custom_settings_activate"]' => ['value' => '1'],
        ],
      ],
    ];
    return $states[$state];
  }

  /**
   * Create a range for a series of options.
   *
   * @param int $start
   *   The start of the range.
   * @param int $end
   *   The end of the range.
   * @param int $step
   *   The interval between elements.
   *
   * @return array
   *   An options array for the given range.
   */
  protected function optionsRange($start, $end, $step) {
    $range = range($start, $end, $step);
    return array_combine($range, $range);
  }

}
