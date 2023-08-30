<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\LinkGeneratorInterface;

/**
 * Plugin for responsive image formatter.
 *
 * @FieldFormatter(
 *   id = "file_image_responsive",
 *   label = @Translation("Responsive image"),
 *   field_types = {
 *     "uri",
 *     "file_uri"
 *   }
 * )
 */
class FileImageResponsiveFormatter extends ImageFormatter {

  /**
   * @var EntityStorageInterface
   */
  protected $responsiveImageStyleStorage;

  /*
   * The image style entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $imageStyleStorage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $formatter = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $formatter->linkGenerator = $container->get('link_generator');
    $formatter->responsiveImageStyleStorage = $container->get('entity_type.manager')->getStorage('responsive_image_style');
    $formatter->imageStyleStorage = $container->get('entity_type.manager')->getStorage('image_style');
    $formatter->currentUser = $container->get('current_user');
    return $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'responsive_image_style' => '',
      'image_link' => '',
      'image_loading' => [
        'attribute' => 'lazy',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $file = $items->getEntity();

    // Collect cache tags to be added for each item in the field.
    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    $image_styles_to_load = array();
    $cache_tags = [];
    if ($responsive_image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $responsive_image_style->getCacheTags());
      $image_styles_to_load = $responsive_image_style->getImageStyleIds();
    }

    $image_styles = $this->imageStyleStorage->loadMultiple($image_styles_to_load);
    foreach ($image_styles as $image_style) {
      $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
    }

    // Extract field item attributes for the theme function, and unset them
    // from the $item so that the field template does not re-render them.
    $item = $file->_referringItem;
    $item_attributes = $item->_attributes;
    unset($item->_attributes);

    if ($this->getSetting('image_link')) {
      $url = $this->fileUrlGenerator->generateString($file->getFileUri());
    }

    $image_loading_settings = $this->getSetting('image_loading');
    if (isset($image_loading_settings['attribute'])) {
      $item_attributes['loading'] = $image_loading_settings['attribute'] ?? 'lazy';
    }

    $elements[] = array(
      '#theme' => 'responsive_image_formatter',
      '#item' => $item,
      '#item_attributes' => $item_attributes,
      '#responsive_image_style_id' => $responsive_image_style ? $responsive_image_style->id() : '',
      '#url' => !empty($url) ? $url : NULL,
      '#cache' => array(
        'tags' => $cache_tags,
      ),
    );

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareView(array $entities_items) {}

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $responsive_image_options = array();
    $responsive_image_styles = $this->responsiveImageStyleStorage->loadMultiple();
    if ($responsive_image_styles && !empty($responsive_image_styles)) {
      foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
        if ($responsive_image_style->hasImageStyleMappings()) {
          $responsive_image_options[$machine_name] = $responsive_image_style->label();
        }
      }
    }

    $elements['responsive_image_style'] = array(
      '#title' => t('Responsive image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('responsive_image_style'),
      '#required' => TRUE,
      '#options' => $responsive_image_options,
      '#description' => array(
        '#markup' => $this->linkGenerator->generate($this->t('Configure Responsive Image Styles'), new Url('entity.responsive_image_style.collection')),
        '#access' => $this->currentUser->hasPermission('administer responsive image styles'),
      ),
    );

    unset($elements['image_link']['#options']['content']);
    unset($elements['image_style']);
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $responsive_image_style = $this->responsiveImageStyleStorage->load($this->getSetting('responsive_image_style'));
    if ($responsive_image_style) {
      $summary[] = t('Responsive image style: @responsive_image_style', array('@responsive_image_style' => $responsive_image_style->label()));

      $link_types = array(
        'file' => t('Linked to file'),
      );
      // Display this setting only if image is linked.
      if (isset($link_types[$this->getSetting('image_link')])) {
        $summary[] = $link_types[$this->getSetting('image_link')];
      }
    }
    else {
      $summary[] = t('Select a responsive image style.');
    }

    $image_loading = $this->getSetting('image_loading');
    if (isset($image_loading['attribute'])) {
      $summary[] = $this->t('Image loading: @attribute', [
        '@attribute' => $image_loading['attribute'],
      ]);
    }

    return $summary;
  }

}
