<?php

namespace Drupal\photoswipe\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class PhotoswipeSettings extends ConfigFormBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Constructs a PhotoswipeSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed configuration manager.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   The library discovery service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, TypedConfigManagerInterface $typedConfigManager, LibraryDiscoveryInterface $libraryDiscovery) {
    parent::__construct($configFactory, $typedConfigManager);
    $this->libraryDiscovery = $libraryDiscovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoswipe_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('photoswipe.settings');

    $form['enable_cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load PhotoSwipe library from CDN'),
      '#default_value' => $config->get('enable_cdn'),
      '#description' => $this->t('Loads the photoswipe library via a content delivery network (CDN).<br><strong>Note, that this may NOT comply with your regional data protection regulations.</strong>'),
    ];

    $form['photoswipe_always_load_non_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load PhotoSwipe globally on all non-admin pages'),
      '#default_value' => $config->get('photoswipe_always_load_non_admin'),
      '#description' => $this->t('Useful if you want to use photoswipe elsewhere by just adding the <code>.photoswipe</code> CSS class.'),
    ];

    $form['showHideAnimationType'] = [
      '#type' => 'select',
      '#options' => [
        'zoom' => $this->t('Zoom in / out on the slide <i>(Default)</i>'),
        'fade' => $this->t('Fade the slide in / out'),
        'none' => $this->t('Statically show the slide'),
      ],
      '#title' => $this->t('Show / Hide Animation Type'),
      '#description' => $this->t('The animation type of the slide opening / closing animation.'),
      '#default_value' => $config->get('options.showHideAnimationType'),
      '#required' => TRUE,
    ];

    $form['showAnimationDuration'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Show Animation Duration'),
      '#default_value' => $config->get('options.showAnimationDuration'),
      '#description' => $this->t('The duration of the "show" transition animation <i>(in milliseconds)</i>.'),
      '#required' => TRUE,
    ];

    $form['hideAnimationDuration'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Hide Animation Duration'),
      '#default_value' => $config->get('options.hideAnimationDuration'),
      '#description' => $this->t('The duration of the "hide" transition animation <i>(in milliseconds)</i>.'),
      '#required' => TRUE,
    ];

    $form['zoomAnimationDuration'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Zoom Animation Duration'),
      '#default_value' => $config->get('options.zoomAnimationDuration'),
      '#description' => $this->t('The duration of the zoom animation <i>(in milliseconds)</i>.'),
      '#required' => TRUE,
    ];

    $form['maxWidthToAnimate'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Max Width to Animate'),
      '#default_value' => $config->get('options.maxWidthToAnimate'),
      '#description' => $this->t('The maximum image width to animate. Images with a size larger than that will not play any animations.'),
      '#required' => TRUE,
    ];

    $form['easing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Easing Method'),
      '#default_value' => $config->get('options.easing'),
      '#description' => $this->t('The easing (smoothing) method for the animations. Accepts any <a href=@link>CSS timing-function</a>.', ['@link' => Url::fromUri('https://developer.mozilla.org/en-US/docs/Web/CSS/transition-timing-function')->toString()]),
      '#required' => TRUE,
    ];

    $form['bgOpacity'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#title' => $this->t('Background Opacity'),
      '#default_value' => $config->get('options.bgOpacity'),
      '#description' => $this->t('The opacity of the background <i>(0 = 0% / 1 = 100%)</i>.'),
      '#required' => TRUE,
    ];

    $form['spacing'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#title' => $this->t('Slides Spacing'),
      '#default_value' => $config->get('options.spacing'),
      '#description' => $this->t('Spacing between slides. Defined as ratio relative to the viewport width (0.1 = 10% of viewport).'),
      '#required' => TRUE,
    ];

    $form['initialZoomLevel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Initial Zoom Level'),
      "#pattern" => "(fit|fill|\d+(\.\d{1,2})?)",
      '#attributes' => [
        "#title" => $this->t("Enter 'fit', 'fill', or a decimal value"),
      ],
      '#default_value' => $config->get('options.initialZoomLevel'),
      '#description' => $this->t('The zoom level when Photoswipe is opened <i>("fit" - image fits into viewport (default),  "fill" - image fills the viewport (similar to background-size:cover),  1 = Original Image Size, 2 = 2x Original Size, etc.)</i>.'),
      '#required' => TRUE,
    ];

    $form['secondaryZoomLevel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secondary Zoom Level'),
      "#pattern" => "(fit|fill|\d+(\.\d{1,2})?)",
      '#attributes' => [
        "#title" => $this->t("Enter 'fit', 'fill', or a decimal value"),
      ],
      '#default_value' => $config->get('options.secondaryZoomLevel'),
      '#description' => $this->t('The zoom level when using the zoom button or a zoom tap on the image <i>("fit" - image fits into viewport (default),  "fill" - image fills the viewport (similar to background-size:cover),  1 = Original Image Size, 2 = 2x Original Size, etc.)</i>.'),
      '#required' => TRUE,
    ];

    $form['maxZoomLevel'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum Zoom Level'),
      "#pattern" => "(fit|fill|\d+(\.\d{1,2})?)",
      '#attributes' => [
        "#title" => $this->t("Enter 'fit', 'fill', or a decimal value"),
      ],
      '#default_value' => $config->get('options.maxZoomLevel'),
      '#description' => $this->t('The maximum amount a user can zoom in on a slide <i>("fit" - image fits into viewport (default),  "fill" - image fills the viewport (similar to background-size:cover),  1 = Original Image Size, 2 = 2x Original Size, etc.)</i>.'),
      '#required' => TRUE,
    ];

    $form['allowPanToNext'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow Panning to Next Slide'),
      '#default_value' => $config->get('options.allowPanToNext'),
      '#description' => $this->t('Whether to allow swipe navigation to the next slide. Note, that this is automatically disabled for non touch devices.'),
    ];

    $form['loop'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Loop Slides'),
      '#default_value' => $config->get('options.loop'),
      '#description' => $this->t('Whether the slides should loop.'),
    ];

    $form['wheelToZoom'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Wheel to Zoom'),
      '#default_value' => $config->get('options.wheelToZoom'),
      '#description' => $this->t('<i>If enabled:</i> Scroll wheel is used for zooming.<br><i>If disabled:</i> Ctrl + Scroll Wheel is used for zooming.'),
    ];

    $form['pinchToClose'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pinch to Close'),
      '#default_value' => $config->get('options.pinchToClose'),
      '#description' => $this->t('Whether a pinch gesture should close the gallery.'),
    ];

    $form['clickToCloseNonZoomable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Click to Close Non-Zoomable'),
      '#default_value' => $config->get('options.clickToCloseNonZoomable'),
      '#description' => $this->t('Whether non-zoomable slides should close when clicked on.'),
    ];

    $form['closeOnVerticalDrag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Close on Vertical Drag'),
      '#default_value' => $config->get('options.closeOnVerticalDrag'),
      '#description' => $this->t('Whether a vertical drag should close the gallery.'),
    ];

    $form['trapFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Trap Focus'),
      '#default_value' => $config->get('options.trapFocus'),
      '#description' => $this->t("Whether to trap the focus of the Photoswipe gallery while it's opened."),
    ];

    $form['returnFocus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Return Focus after Close'),
      '#default_value' => $config->get('options.returnFocus'),
      '#description' => $this->t('Whether to restore focus to the last active element after PhotoSwipe is closed.'),
    ];

    $form['escKey'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('ESC Key to Close'),
      '#default_value' => $config->get('options.escKey'),
      '#description' => $this->t('Whether pressing the ESC key should close the gallery.'),
    ];

    $form['arrowKeys'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Arrow Keys to Navigate'),
      '#default_value' => $config->get('options.arrowKeys'),
      '#description' => $this->t('Whether you can press the arrow keys to navigate the slides.'),
    ];

    $form['imageClickAction'] = [
      '#type' => 'select',
      '#options' => [
        'zoom' => $this->t('Zoom in on the current slide'),
        'zoom-or-close' => $this->t('Zoom in or close, if the current slide is not zoomable <i>(Default)</i>'),
        'toggle-controls' => $this->t('Toggle the visibility of the slide controls'),
        'next' => $this->t('Move to the next slide'),
        'close' => $this->t('Close the gallery'),
      ],
      '#title' => $this->t('Image Click Action'),
      '#description' => $this->t('The action performed by PhotoSwipe when an image is being clicked on.'),
      '#default_value' => $config->get('options.imageClickAction'),
      '#required' => TRUE,
    ];

    $form['tapAction'] = [
      '#type' => 'select',
      '#options' => [
        'zoom' => $this->t('Zoom in on the current slide'),
        'zoom-or-close' => $this->t('Zoom in or close, if the current slide is not zoomable'),
        'toggle-controls' => $this->t('Toggle the visibility of the slide controls <i>(Default)</i>'),
        'next' => $this->t('Move to the next slide'),
        'close' => $this->t('Close the gallery'),
      ],
      '#title' => $this->t('Tap Action'),
      '#description' => $this->t('The action performed by PhotoSwipe when a slide is being tapped on.'),
      '#default_value' => $config->get('options.tapAction'),
      '#required' => TRUE,
    ];

    $form['doubleTapAction'] = [
      '#type' => 'select',
      '#options' => [
        'zoom' => $this->t('Zoom in on the current slide <i>(Default)</i>'),
        'zoom-or-close' => $this->t('Zoom in or close, if the current slide is not zoomable'),
        'toggle-controls' => $this->t('Toggle the visibility of the slide controls'),
        'next' => $this->t('Move to the next slide'),
        'close' => $this->t('Close the gallery'),
        'false' => $this->t('Do nothing (Tap delay will also be removed)'),
      ],
      '#title' => $this->t('Double Tap Action'),
      '#description' => $this->t('The action performed by PhotoSwipe when a slide is being double-tapped on.'),
      '#default_value' => $config->get('options.doubleTapAction'),
      '#required' => TRUE,
    ];

    $form['bgClickAction'] = [
      '#type' => 'select',
      '#options' => [
        'zoom' => $this->t('Zoom in on the current slide'),
        'zoom-or-close' => $this->t('Zoom in or close, if the current slide is not zoomable <i>(Default)</i>'),
        'toggle-controls' => $this->t('Toggle the visibility of the slide controls'),
        'next' => $this->t('Move to the next slide'),
        'close' => $this->t('Close the gallery <i>(Default)</i>'),
      ],
      '#title' => $this->t('Background Click Action'),
      '#description' => $this->t('The action performed by PhotoSwipe when the background of a slide is being clicked.'),
      '#default_value' => $config->get('options.bgClickAction'),
      '#required' => TRUE,
    ];

    $form['closeTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gallery Close Tooltip'),
      '#default_value' => $config->get('options.closeTitle'),
      '#description' => $this->t('The tooltip that should be displayed when a user hovers over the gallery close button.'),
      '#required' => TRUE,
    ];

    $form['zoomTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoom Tooltip'),
      '#default_value' => $config->get('options.zoomTitle'),
      '#description' => $this->t('The tooltip that should be displayed when a user hovers over the zoom button.'),
      '#required' => TRUE,
    ];

    $form['arrowPrevTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Previous Arrow Tooltip'),
      '#default_value' => $config->get('options.arrowPrevTitle'),
      '#description' => $this->t('The tooltip that should be displayed when a user hovers over the arrow to the previous image.'),
      '#required' => TRUE,
    ];

    $form['arrowNextTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Next Arrow Tooltip'),
      '#default_value' => $config->get('options.arrowNextTitle'),
      '#description' => $this->t('The tooltip that should be displayed when a user hovers over the arrow to the next image.'),
      '#required' => TRUE,
    ];

    $form['downloadTitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Download Aria Label'),
      '#default_value' => $config->get('options.downloadTitle'),
      '#description' => $this->t('The aria-label that should be set on the download button.'),
      '#required' => TRUE,
    ];

    $form['indexIndicatorSep'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Index Indicator Separator'),
      '#default_value' => $config->get('options.indexIndicatorSep'),
      '#description' => $this->t('The separator between the index indicators <i>(e.g. "/" = "1/10", " of " = "1 of 10", etc.)</i>.'),
      '#required' => TRUE,
    ];

    $form['errorMsg'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Error Message'),
      '#default_value' => $config->get('options.errorMsg'),
      '#description' => $this->t('The message that will be displayed if an image cannot be shown or loaded properly.'),
      '#required' => TRUE,
    ];

    $form['preloadBefore'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#title' => $this->t('Preload Slides Before'),
      '#default_value' => $config->get('options.preload')[0],
      '#description' => $this->t('The amount of slides to preload before the current slide. <br><strong>Note, preloading too many slides, may impact performance.</strong>'),
      '#required' => TRUE,
    ];

    $form['preloadAfter'] = [
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#title' => $this->t('Preload Slides After'),
      '#default_value' => $config->get('options.preload')[1],
      '#description' => $this->t('The amount of slides to preload after the current slide. <br><strong>Note, preloading too many slides, may impact performance.</strong>'),
      '#required' => TRUE,
    ];

    $form['preloaderDelay'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Preloader Delay'),
      '#default_value' => $config->get('options.preloaderDelay'),
      '#description' => $this->t('The amount of time to pass while loading before the loading indicator will be displayed <i>(in milliseconds)</i>.'),
      '#required' => TRUE,
    ];

    $form['mainClass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Main Class'),
      '#default_value' => $config->get('options.mainClass'),
      '#description' => $this->t('Class that will be added to the root element of PhotoSwipe, may contain multiple separated by space.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('photoswipe.settings')
      ->set('photoswipe_always_load_non_admin', $form_state->getValue('photoswipe_always_load_non_admin'))
      ->set('enable_cdn', $form_state->getValue('enable_cdn'))
      ->set('options.showHideAnimationType', $form_state->getValue('showHideAnimationType'))
      ->set('options.showAnimationDuration', $form_state->getValue('showAnimationDuration'))
      ->set('options.hideAnimationDuration', $form_state->getValue('hideAnimationDuration'))
      ->set('options.zoomAnimationDuration', $form_state->getValue('zoomAnimationDuration'))
      ->set('options.maxWidthToAnimate', $form_state->getValue('maxWidthToAnimate'))
      ->set('options.easing', $form_state->getValue('easing'))
      ->set('options.bgOpacity', $form_state->getValue('bgOpacity'))
      ->set('options.spacing', $form_state->getValue('spacing'))
      ->set('options.initialZoomLevel', $form_state->getValue('initialZoomLevel'))
      ->set('options.secondaryZoomLevel', $form_state->getValue('secondaryZoomLevel'))
      ->set('options.maxZoomLevel', $form_state->getValue('maxZoomLevel'))
      ->set('options.allowPanToNext', $form_state->getValue('allowPanToNext'))
      ->set('options.loop', $form_state->getValue('loop'))
      ->set('options.wheelToZoom', $form_state->getValue('wheelToZoom'))
      ->set('options.pinchToClose', $form_state->getValue('pinchToClose'))
      ->set('options.clickToCloseNonZoomable', $form_state->getValue('clickToCloseNonZoomable'))
      ->set('options.closeOnVerticalDrag', $form_state->getValue('closeOnVerticalDrag'))
      ->set('options.trapFocus', $form_state->getValue('trapFocus'))
      ->set('options.returnFocus', $form_state->getValue('returnFocus'))
      ->set('options.escKey', $form_state->getValue('escKey'))
      ->set('options.arrowKeys', $form_state->getValue('arrowKeys'))
      ->set('options.imageClickAction', $form_state->getValue('imageClickAction'))
      ->set('options.tapAction', $form_state->getValue('tapAction'))
      ->set('options.doubleTapAction', $form_state->getValue('doubleTapAction'))
      ->set('options.bgClickAction', $form_state->getValue('bgClickAction'))
      ->set('options.closeTitle', $form_state->getValue('closeTitle'))
      ->set('options.zoomTitle', $form_state->getValue('zoomTitle'))
      ->set('options.arrowPrevTitle', $form_state->getValue('arrowPrevTitle'))
      ->set('options.arrowNextTitle', $form_state->getValue('arrowNextTitle'))
      ->set('options.downloadTitle', $form_state->getValue('downloadTitle'))
      ->set('options.indexIndicatorSep', $form_state->getValue('indexIndicatorSep'))
      ->set('options.errorMsg', $form_state->getValue('errorMsg'))
      ->set('options.preload', [
        $form_state->getValue('preloadBefore'),
        $form_state->getValue('preloadAfter'),
      ])
      ->set('options.preloaderDelay', $form_state->getValue('preloaderDelay'))
      ->set('options.mainClass', $form_state->getValue('mainClass'))
      ->save();
    parent::submitForm($form, $form_state);

    // CDN vs local is chosen in library_info_alter.
    // clearCachedDefinitions() is deprecated since Drupal 11.1 in favor of
    // clear(), which doesn't exist in Drupal 10:
    // @phpstan-ignore-next-line
    $this->libraryDiscovery->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['photoswipe.settings'];
  }

}
