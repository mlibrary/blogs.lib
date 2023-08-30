<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\image\Plugin\Field\FieldFormatter\ImageFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implementation of the 'image' formatter for the file_entity files.
 *
 * @FieldFormatter(
 *   id = "file_image",
 *   label = @Translation("File Image"),
 *   field_types = {
 *     "uri",
 *     "file_uri"
 *   }
 * )
 */
class FileImageFormatter extends ImageFormatter {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $formatter = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $formatter->entityFieldManager = $container->get('entity_field.manager');
    return $formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title' => 'field_image_title_text',
      'alt' => 'field_image_alt_text',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    if ($this->getSetting('title') == '_none') {
      $summary[] = $this->t('Title attribute is hidden.');
    } else {
      $summary[] = $this->t('Field used for the image title attribute: @title', ['@title' => $this->getSetting('title')]);
    }
    if ($this->getSetting('alt') == '_none') {
      $summary[] = $this->t('Alt attribute is hidden.');
    } else {
      $summary[] = $this->t('Field used for the image alt attribute: @alt', ['@alt' => $this->getSetting('alt')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $file = $items->getEntity();

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = [];
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }
    $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());

    if (isset($image_style)) {
      $elements[0] = [
        '#theme' => 'image_style',
        '#style_name' => $image_style_setting,
      ];
    }
    else {
      $elements[0] = [
        '#theme' => 'image',
      ];
    }
    $elements[0] += [
      '#uri' => $file->getFileUri(),
      '#cache' => [
        'tags' => $cache_tags,
      ],
    ];
    foreach (['title', 'alt'] as $element_name) {
      $field_name = $this->getSetting($element_name);
      if ($field_name !== '_none' && $file->hasField($field_name)) {
        $elements[0]['#' . $element_name] = $file->$field_name->value;
      }
    }
    $image_loading_settings = $this->getSetting('image_loading');
    if (isset($image_loading_settings['attribute'])) {
      $elements[0]['#attributes']['loading'] = $image_loading_settings['attribute'];
    }

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
    $element = parent::settingsForm($form, $form_state);
    unset($element['image_link']);
    $available_fields = $this->entityFieldManager->getFieldDefinitions(
      $form['#entity_type'],
      $form['#bundle']
    );
    $options = [];
    foreach ($available_fields as $field_name => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface && $field_definition->getType() == 'string') {
        $options[$field_name] = $field_definition->label();
      }
    }
    $element['title'] = [
      '#title' => $this->t('Image title field'),
      '#description' => $this->t('The field that is used as source for the image title attribute.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('title'),
      '#empty_option' => $this->t('No title attribute'),
      '#empty_value' => '_none',
    ];
    $element['alt'] = [
      '#title' => $this->t('Image alt field'),
      '#description' => $this->t('The field that is used as source for the image alt attribute.'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $this->getSetting('alt'),
      '#empty_option' => $this->t('No alt attribute'),
      '#empty_value' => '_none',
    ];
    return $element;
  }

}
