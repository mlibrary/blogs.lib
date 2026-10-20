<?php

namespace Drupal\photoswipe_svg_image_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\photoswipe\PhotoswipeAssetsManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'photoswipe_svg_image_field_formatter'.
 *
 * @FieldFormatter(
 *   id = "photoswipe_svg_image_field_formatter",
 *   label = @Translation("Photoswipe"),
 *   field_types = {
 *     "svg_image_field"
 *   }
 * )
 */
class PhotoswipeSvgImageFieldFormatter extends FormatterBase {

  /**
   * The assets manager.
   *
   * @var \Drupal\photoswipe\PhotoswipeAssetsManagerInterface
   */
  protected $photoswipeAssetManager;

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'photoswipe_thumbnail_width' => 100,
      'photoswipe_thumbnail_height' => 100,
      'photoswipe_remove_gallery_wrapper_class' => FALSE,
      'photoswipe_show_download_button' => FALSE,
      'image_loading' => [
        'attribute' => 'lazy',
      ],
    ] + parent::defaultSettings();
  }

  /**
   * Constructs a PhotoswipeSvgImageFieldFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\photoswipe\PhotoswipeAssetsManagerInterface $assets_manager
   *   The assets manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    $label,
    $view_mode,
    array $third_party_settings,
    PhotoswipeAssetsManagerInterface $assets_manager,
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->photoswipeAssetManager = $assets_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('photoswipe.assets_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['photoswipe_thumbnail_width'] = [
      '#title' => $this->t('Thumbnail width'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $this->getSetting('photoswipe_thumbnail_width'),
      '#description' => $this->t('Width, in pixels, to render the thumbnail SVG at. Clicking the thumbnail opens the original SVG in the Photoswipe layer.'),
    ];
    $element['photoswipe_thumbnail_height'] = [
      '#title' => $this->t('Thumbnail height'),
      '#type' => 'number',
      '#min' => 1,
      '#default_value' => $this->getSetting('photoswipe_thumbnail_height'),
      '#description' => $this->t('Height, in pixels, to render the thumbnail SVG at.'),
    ];

    $image_loading = $this->getSetting('image_loading');
    $element['image_loading'] = [
      '#type' => 'details',
      '#title' => $this->t('Image loading'),
      '#weight' => 10,
      '#description' => $this->t('Lazy render images with native image loading attribute (<em>loading="lazy"</em>). This improves performance by allowing browsers to lazily load images.'),
    ];
    $loading_attribute_options = [
      'lazy' => $this->t('Lazy (<em>loading="lazy"</em>)'),
      'eager' => $this->t('Eager (<em>loading="eager"</em>)'),
    ];
    $element['image_loading']['attribute'] = [
      '#title' => $this->t('Image loading attribute'),
      '#type' => 'radios',
      '#default_value' => $image_loading['attribute'],
      '#options' => $loading_attribute_options,
      '#description' => $this->t('Select the loading attribute for images. <a href=":link">Learn more about the loading attribute for images.</a>', [
        ':link' => 'https://html.spec.whatwg.org/multipage/urls-and-fetching.html#lazy-loading-attributes',
      ]),
    ];

    $element['photoswipe_remove_gallery_wrapper_class'] = [
      '#title' => $this->t('Remove photoswipe-gallery wrapper class'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('photoswipe_remove_gallery_wrapper_class'),
      '#description' => $this->t('Advanced: Removes the default "photoswipe-gallery" wrapper class. This is useful to combine multiple fields into a single gallery by wrapping them in a custom parent container with the "photoswipe-gallery" class, e.g. through Views or Fences.'),
    ];
    $element['photoswipe_show_download_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show download button'),
      '#default_value' => $this->getSetting('photoswipe_show_download_button'),
      '#description' => $this->t('Display a download button in the PhotoSwipe toolbar.'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings][photoswipe_remove_gallery_wrapper_class]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Thumbnail size: @width x @height px', [
      '@width' => $this->getSetting('photoswipe_thumbnail_width'),
      '@height' => $this->getSetting('photoswipe_thumbnail_height'),
    ]);

    $image_loading = $this->getSetting('image_loading');
    $summary[] = $this->t('Image loading: @attribute', [
      '@attribute' => $image_loading['attribute'],
    ]);

    $removeWrapperClassStatus = $this->getSetting('photoswipe_remove_gallery_wrapper_class') ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t('Remove photoswipe-gallery wrapper class: %status', ['%status' => $removeWrapperClassStatus]);

    $showDownloadButton = $this->getSetting('photoswipe_show_download_button') ? $this->t('Yes') : $this->t('No');
    $summary[] = $this->t('Show download button: %status', ['%status' => $showDownloadButton]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function view(FieldItemListInterface $items, $langcode = NULL) {
    $elements = parent::view($items, $langcode);
    if (empty($elements['#attributes'])) {
      $elements['#attributes'] = new Attribute();
    }
    if (!$this->getSetting('photoswipe_remove_gallery_wrapper_class')) {
      $elements['#attributes']->addClass('photoswipe-gallery');
      if ($this->getSetting('photoswipe_show_download_button')) {
        $elements['#attributes']->setAttribute('data-show-download-button', 'true');
      }
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $overriddenOptions = [];

    foreach ($items as $delta => $item) {
      if (!$item->entity instanceof EntityInterface || !$item->entity->access('view')) {
        continue;
      }

      $item_attributes = $item->_attributes ?: [];
      $image_loading_settings = $this->getSetting('image_loading');
      $item_attributes['loading'] = $image_loading_settings['attribute'];

      $elements[$delta] = [
        '#theme' => 'photoswipe_svg_image_field_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#entity' => $items->getEntity(),
        '#display_settings' => $this->getSettings(),
        '#third_party_settings' => $this->getThirdPartySettings(),
        '#delta' => $delta,
      ];
      $this->photoswipeAssetManager->attach($elements[$delta], $overriddenOptions);
    }
    return $elements;
  }

}
