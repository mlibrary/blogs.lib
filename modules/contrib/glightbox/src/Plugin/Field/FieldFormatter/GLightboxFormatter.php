<?php

namespace Drupal\glightbox\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;
use Drupal\glightbox\ElementAttachmentInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatterBase;

/**
 * Plugin implementation of the 'glightbox' formatter.
 *
 * @FieldFormatter(
 *   id = "glightbox",
 *   module = "glightbox",
 *   label = @Translation("GLightbox"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class GLightboxFormatter extends ImageFormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The image style entity storage.
   *
   * @var \Drupal\image\ImageStyleStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * Element attachment allowing library to be attached to pages.
   *
   * @var \Drupal\glightbox\ElementAttachmentInterface
   */
  protected $attachment;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  private $libraryDiscovery;

  /**
   * Constructs an ImageFormatter object.
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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityStorageInterface $image_style_storage
   *   The image style storage.
   * @param \Drupal\glightbox\ElementAttachmentInterface $attachment
   *   Allow the library to be attached to the page.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler services.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $libraryDiscovery
   *   Library discovery service.
   */
  public function __construct($plugin_id,
                              $plugin_definition,
                              FieldDefinitionInterface $field_definition,
                              array $settings,
                              $label,
                              $view_mode,
                              array $third_party_settings,
                              AccountInterface $current_user,
                              EntityStorageInterface $image_style_storage,
                              ElementAttachmentInterface $attachment,
                              ModuleHandlerInterface $moduleHandler,
                              LibraryDiscoveryInterface $libraryDiscovery) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
    $this->attachment = $attachment;
    $this->moduleHandler = $moduleHandler;
    $this->libraryDiscovery = $libraryDiscovery;
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
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style'),
      $container->get('glightbox.attachment'),
      $container->get('module_handler'),
      $container->get('library.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'glightbox_node_style' => '',
      'glightbox_node_style_first' => '',
      'glightbox_image_style' => '',
      'glightbox_gallery' => 'post',
      'glightbox_gallery_custom' => '',
      'glightbox_caption' => 'auto',
      'glightbox_caption_custom' => '',
      'glightbox_caption_description' => '',
      'glightbox_caption_description_custom' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $image_styles = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service('Drupal\image\ImageDerivativeUtilities')->styleOptions(FALSE), fn() => image_style_options(FALSE));
    $image_styles_hide = $image_styles;
    $image_styles_hide['hide'] = $this->t('Hide (do not display image)');
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );

    $element['glightbox_node_style'] = [
      '#title' => $this->t('Image style for content'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('glightbox_node_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles_hide,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $element['glightbox_node_style_first'] = [
      '#title' => $this->t('Image style for first image in content'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('glightbox_node_style_first'),
      '#empty_option' => $this->t('No special style.'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];
    $element['glightbox_image_style'] = [
      '#title' => $this->t('Image style for GLightbox'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('glightbox_image_style'),
      '#empty_option' => $this->t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
        '#access' => $this->currentUser->hasPermission('administer image styles'),
      ],
    ];

    $gallery = [
      'post' => $this->t('Per post gallery'),
      'page' => $this->t('Per page gallery'),
      'parent' => $this->t('Per parent entity gallery (e.g. Media field or Paragraph with image fields)'),
      'paragraph' => $this->t('Per paragraph gallery (e.g Paragraphs with Media fields)'),
      'field_post' => $this->t('Per field in post gallery'),
      'field_page' => $this->t('Per field in page gallery'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('No gallery'),
    ];
    $element['glightbox_gallery'] = [
      '#title' => $this->t('Gallery (image grouping)'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('glightbox_gallery'),
      '#options' => $gallery,
      '#description' => $this->t('How GLightbox should group the image galleries.'),
    ];
    $element['glightbox_gallery_custom'] = [
      '#title' => $this->t('Custom gallery'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('glightbox_gallery_custom'),
      '#description' => $this->t('All images on a page with the same gallery value (rel attribute) will be grouped together. It must only contain lowercase letters, numbers, and underscores.'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][glightbox_gallery]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $entity_type = '';

      if (isset($form['#entity_type']) && !empty($form['#entity_type'])) {
        $entity_type = $form['#entity_type'];
      }

      $element['glightbox_token_gallery'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#theme' => 'token_tree_link',
        '#token_types' => [$entity_type, 'file', 'paragraph', 'paragraph_container', 'node'],
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][glightbox_gallery]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }
    else {
      $element['glightbox_token_gallery'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
        '#states' => [
          'visible' => [
            ':input[name$="[settings_edit_form][settings][glightbox_gallery]"]' => ['value' => 'custom'],
          ],
        ],
      ];
    }

    $caption = [
      'auto' => $this->t('Automatic'),
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
      'entity_title' => $this->t('Content title'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('None'),
    ];
    $element['glightbox_caption'] = [
      '#title' => $this->t('Caption'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('glightbox_caption'),
      '#options' => $caption,
      '#description' => $this->t('Automatic will use the first non-empty value out of the title, the alt text and the content title.'),
    ];
    $element['glightbox_caption_custom'] = [
      '#title' => $this->t('Custom caption'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('glightbox_caption_custom'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][glightbox_caption]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    $element['glightbox_caption_description'] = [
      '#title' => $this->t('Caption description'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('glightbox_caption_description'),
      '#options' => $caption,
      '#description' => $this->t('Same as caption but supports HTML.'),
    ];
    $element['glightbox_caption_description_custom'] = [
      '#title' => $this->t('Custom caption description'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('glightbox_caption_description_custom'),
      '#states' => [
        'visible' => [
          ':input[name$="[settings_edit_form][settings][glightbox_caption_description]"]' => ['value' => 'custom'],
        ],
      ],
    ];
    if ($this->moduleHandler->moduleExists('token')) {
      $entity_type = '';

      if (isset($form['#entity_type']) && !empty($form['#entity_type'])) {
        $entity_type = $form['#entity_type'];
      }

      $element['glightbox_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#theme' => 'token_tree_link',
        '#token_types' => [$entity_type, 'file', 'paragraph', 'paragraph_container', 'node'],
        '#states' => [
          'visible' => [
            [
              ':input[name$="[settings_edit_form][settings][glightbox_caption]"]' => ['value' => 'custom'],
            ],
            'or',
            [
              ':input[name$="[settings_edit_form][settings][glightbox_caption_description]"]' => ['value' => 'custom'],
            ],
          ],
        ],
      ];
    }
    else {
      $element['glightbox_token_caption'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Replacement patterns'),
        '#description' => '<strong class="error">' . $this->t('For token support the <a href="@token_url">token module</a> must be installed.', ['@token_url' => 'http://drupal.org/project/token']) . '</strong>',
        '#states' => [
          'visible' => [
            [
              ':input[name$="[settings_edit_form][settings][glightbox_caption]"]' => ['value' => 'custom'],
            ],
            'or',
            [
              ':input[name$="[settings_edit_form][settings][glightbox_caption_description]"]' => ['value' => 'custom'],
            ],
          ],
        ],
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $image_styles = DeprecationHelper::backwardsCompatibleCall(\Drupal::VERSION, '11.4.0', fn() => \Drupal::service('Drupal\image\ImageDerivativeUtilities')->styleOptions(FALSE), fn() => image_style_options(FALSE));
    // Unset possible 'No defined styles' option.
    unset($image_styles['']);
    // Styles could be lost because of enabled/disabled modules that defines
    // their styles in code.
    if (isset($image_styles[$this->getSetting('glightbox_node_style')])) {
      $summary[] = $this->t('Content image style: @style', ['@style' => $image_styles[$this->getSetting('glightbox_node_style')]]);
    }
    elseif ($this->getSetting('glightbox_node_style') == 'hide') {
      $summary[] = $this->t('Content image style: Hide');
    }
    else {
      $summary[] = $this->t('Content image style: Original image');
    }

    if (isset($image_styles[$this->getSetting('glightbox_node_style_first')])) {
      $summary[] = $this->t('Content image style of first image: @style', ['@style' => $image_styles[$this->getSetting('glightbox_node_style_first')]]);
    }

    if (isset($image_styles[$this->getSetting('glightbox_image_style')])) {
      $summary[] = $this->t('GLightbox image style: @style', ['@style' => $image_styles[$this->getSetting('glightbox_image_style')]]);
    }
    else {
      $summary[] = $this->t('GLightbox image style: Original image');
    }

    $gallery = [
      'post' => $this->t('Per post gallery'),
      'page' => $this->t('Per page gallery'),
      'parent' => $this->t('Per parent entity gallery (e.g. Media field or Paragraph with image fields)'),
      'paragraph' => $this->t('Per paragraph gallery (e.g. Paragraphs with Media fields)'),
      'field_post' => $this->t('Per field in post gallery'),
      'field_page' => $this->t('Per field in page gallery'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('No gallery'),
    ];
    if ($this->getSetting('glightbox_gallery')) {
      $summary[] = $this->t('GLightbox gallery type: @type', ['@type' => $gallery[$this->getSetting('glightbox_gallery')]]) . ($this->getSetting('glightbox_gallery') == 'custom' ? ' (' . $this->getSetting('glightbox_gallery_custom') . ')' : '');
    }

    $caption = [
      'auto' => $this->t('Automatic'),
      'title' => $this->t('Title text'),
      'alt' => $this->t('Alt text'),
      'entity_title' => $this->t('Content title'),
      'custom' => $this->t('Custom (with tokens)'),
      'none' => $this->t('None'),
    ];

    if ($this->getSetting('glightbox_caption')) {
      $summary[] = $this->t('GLightbox caption: @type', ['@type' => $caption[$this->getSetting('glightbox_caption')]]);
    }
    if ($this->getSetting('glightbox_caption_description')) {
      $summary[] = $this->t('GLightbox caption description: @type', ['@type' => $caption[$this->getSetting('glightbox_caption')]]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $settings = $this->getSettings();
    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($settings['glightbox_node_style']) && $settings['glightbox_node_style'] != 'hide') {
      $image_style = $this->imageStyleStorage->load($settings['glightbox_node_style']);
      $cache_tags = $image_style->getCacheTags();
    }
    $cache_tags_first = [];
    if (!empty($settings['glightbox_node_style_first'])) {
      $image_style_first = $this->imageStyleStorage->load($settings['glightbox_node_style_first']);
      $cache_tags_first = $image_style_first->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      // Check if first image should have separate image style.
      if ($delta == 0 && !empty($settings['glightbox_node_style_first'])) {
        $settings['style_first'] = TRUE;
        $settings['style_name'] = $settings['glightbox_node_style_first'];
        $cache_tags = Cache::mergeTags($cache_tags_first, $file->getCacheTags());
      }
      else {
        $settings['style_first'] = FALSE;
        $settings['style_name'] = $settings['glightbox_node_style'];
        $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());
      }

      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = [
        '#theme' => 'glightbox_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#entity' => $items->getEntity(),
        '#settings' => $settings,
        '#cache' => [
          'tags' => $cache_tags,
        ],
      ];
    }

    // Attach the GLightbox JS and CSS.
    if ($this->attachment->isApplicable()) {
      $this->attachment->attach($elements);
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $style_ids = [];
    $style_ids[] = $this->getSetting('glightbox_node_style');
    if (!empty($this->getSetting('glightbox_node_style_first'))) {
      $style_ids[] = $this->getSetting('glightbox_node_style_first');
    }
    $style_ids[] = $this->getSetting('glightbox_image_style');
    foreach ($style_ids as $style_id) {
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        // If this formatter uses a valid image style to display the image, add
        // the image style configuration entity as dependency of this formatter.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);
    $style_ids = [];
    $style_ids['glightbox_node_style'] = $this->getSetting('glightbox_node_style');
    if (!empty($this->getSetting('glightbox_node_style_first'))) {
      $style_ids['glightbox_node_style_first'] = $this->getSetting('glightbox_node_style_first');
    }
    $style_ids['glightbox_image_style'] = $this->getSetting('glightbox_image_style');
    foreach ($style_ids as $name => $style_id) {
      /** @var \Drupal\image\ImageStyleInterface $style */
      if ($style_id && $style = ImageStyle::load($style_id)) {
        if (!empty($dependencies[$style->getConfigDependencyKey()][$style->getConfigDependencyName()])) {
          $replacement_id = $this->imageStyleStorage->getReplacementId($style_id);
          // If a valid replacement has been provided in the storage,
          // replace the image style with the replacement and signal
          // that the formatter plugin.
          // Settings were updated.
          if ($replacement_id && ImageStyle::load($replacement_id)) {
            $this->setSetting($name, $replacement_id);
            $changed = TRUE;
          }
        }
      }
    }
    return $changed;
  }

}
