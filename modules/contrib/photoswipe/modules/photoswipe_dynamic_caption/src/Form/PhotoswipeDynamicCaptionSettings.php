<?php

namespace Drupal\photoswipe_dynamic_caption\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * {@inheritdoc}
 */
class PhotoswipeDynamicCaptionSettings extends ConfigFormBase {

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\LibraryDiscovery\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * Lazy factory for the libraries directory file finder service.
   *
   * @var \Closure
   */
  protected $libraryFileFinderFactory;

  /**
   * Constructs a PhotoswipeDynamicCaptionSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed configuration manager.
   * @param \Drupal\Core\LibraryDiscovery\LibraryDiscoveryInterface $libraryDiscovery
   *   The library discovery service.
   * @param \Closure $libraryFileFinderFactory
   *   Lazy factory returning the libraries directory file finder service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, TypedConfigManagerInterface $typedConfigManager, LibraryDiscoveryInterface $libraryDiscovery, \Closure $libraryFileFinderFactory) {
    parent::__construct($configFactory, $typedConfigManager);
    $this->libraryDiscovery = $libraryDiscovery;
    $this->libraryFileFinderFactory = $libraryFileFinderFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('library.discovery'),
      static fn () => $container->get('library.libraries_directory_file_finder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'photoswipe_dynamic_caption_settings';
  }

  /**
   * Gets the library file finder service.
   */
  protected function getLibraryFileFinder() {
    return ($this->libraryFileFinderFactory)();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('photoswipe_dynamic_caption.settings');

    // Warn when HTML captions are enabled but DOMPurify is not installed.
    if (!empty($config->get('allowHtmlCaptions'))) {
      if (empty($this->getLibraryFileFinder()->find('dompurify/dist/purify.min.js'))) {
        $this->messenger()->addWarning($this->t('<em>Allow HTML captions</em> setting is enabled, but the DOMPurify library was not found at <code>libraries/dompurify/dist/purify.min.js</code>. Captions will be escaped as plain text until the library is installed. Download it from <a href=":url" target="_blank" rel="noopener noreferrer">DOMPurify releases</a> or see the README.md for details.', [
          ':url' => 'https://github.com/cure53/DOMPurify/releases/latest',
        ]));
      }
    }

    $form['type'] = [
      '#type' => 'radios',
      '#options' => [
        'auto' => $this->t('Auto - Try to automatically determine the best position (depending on available space) <i>(Default)</i>'),
        'below' => $this->t('Below - The caption will always be place below the image'),
        'aside' => $this->t('Aside - The caption will always be placed to the right side of the image'),
      ],
      '#title' => $this->t('Caption Position'),
      '#description' => $this->t('The position type of the caption.'),
      '#default_value' => $config->get('options.type'),
      '#required' => TRUE,
    ];

    $form['mobileLayoutBreakpoint'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Mobile Layout Breakpoint'),
      '#default_value' => $config->get('options.mobileLayoutBreakpoint'),
      '#description' => $this->t('Maximum window width at which the mobile layout should be used.'),
      '#required' => TRUE,
    ];

    $form['horizontalEdgeThreshold'] = [
      '#type' => 'number',
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Horizontal Edge Threshold'),
      '#default_value' => $config->get('options.horizontalEdgeThreshold'),
      '#description' => $this->t('When the horizontal position value of the caption is below this threshold, the class <code>pswp__dynamic-caption--on-hor-edge"</code> gets applied on the caption element<br>(this class can be used to add different styling to the caption, such as horizontal padding).'),
      '#required' => TRUE,
    ];

    $form['mobileCaptionOverlapRatio'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 1,
      '#step' => 0.01,
      '#title' => $this->t('Mobile Caption Overlap Ratio'),
      '#default_value' => $config->get('options.mobileCaptionOverlapRatio'),
      '#description' => $this->t('Defines the amount of horizontal empty space before the mobile caption switches to the "overlap" layout <i>(e.g. 0.3 = 30% of screen, 1 = full screen, etc.)</i>.'),
      '#required' => TRUE,
    ];

    $form['verticallyCenterImage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Vertically Center Image'),
      '#default_value' => $config->get('options.verticallyCenterImage'),
      '#description' => $this->t('If enabled, the image will always be vertically centered in the remaining space between caption and the rest of viewport.<br> If disabled, the image will only be raised, if the caption does not fit below the picture.'),
    ];

    $form['allowHtmlCaptions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow HTML captions'),
      '#default_value' => $config->get('allowHtmlCaptions'),
      '#description' => $this->t('If enabled, captions may contain sanitized HTML via the optional <a href=":url" target="_blank" rel="noopener noreferrer">DOMPurify</a> library (<code>libraries/dompurify/dist/purify.min.js</code>). If disabled or DOMPurify is not installed, captions are escaped as plain text.', [
        ':url' => 'https://github.com/cure53/DOMPurify/releases/latest',
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('photoswipe_dynamic_caption.settings')
      ->set('options.type', $form_state->getValue('type'))
      ->set('options.mobileLayoutBreakpoint', $form_state->getValue('mobileLayoutBreakpoint'))
      ->set('options.horizontalEdgeThreshold', $form_state->getValue('horizontalEdgeThreshold'))
      ->set('options.mobileCaptionOverlapRatio', $form_state->getValue('mobileCaptionOverlapRatio'))
      ->set('options.verticallyCenterImage', $form_state->getValue('verticallyCenterImage'))
      ->set('allowHtmlCaptions', $form_state->getValue('allowHtmlCaptions'))
      ->save();
    parent::submitForm($form, $form_state);

    // DOMPurify is attached via library_info_alter when allowHtmlCaptions is
    // enabled.
    $this->libraryDiscovery->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['photoswipe_dynamic_caption.settings'];
  }

}
