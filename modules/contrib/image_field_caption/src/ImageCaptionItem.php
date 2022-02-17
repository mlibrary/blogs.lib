<?php

/**
 * @file
 * Contains \Drupal\image_field_caption\ImageCaptionItem.
 */

namespace Drupal\image_field_caption;

use Drupal\Core\Form\FormStateInterface;
use Drupal\image\Plugin\Field\FieldType\ImageItem;

class ImageCaptionItem extends ImageItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'default_image' => array(
        'caption' => '',
      ),
      'caption_field' => FALSE,
      'caption_field_required' => FALSE,
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    // Get base form from ImageItem.
    $element = parent::fieldSettingsForm($form, $form_state);
    // Get field settings.
    $settings = $this->getSettings();
    // Get the default field settings.
    $settings_default = self::defaultFieldSettings();

    // Add caption option.
    $element['caption_field'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable <em>Caption</em> field'),
      '#default_value' => (!empty($settings['caption_field'])) ? $settings['caption_field'] : $settings_default['caption_field'],
      '#description' => t('Adds an extra text area for captions on image fields.'),
      '#weight' => 13,
    );
    // Add caption (required) option.
    $element['caption_field_required'] = array(
      '#type' => 'checkbox',
      '#title' => t('<em>Caption</em> field required'),
      '#default_value' => $settings['caption_field_required'],
      '#description' => '',
      '#weight' => 14,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[image_caption_field]"]' => array('checked' => TRUE),
        ),
      ),
    );
    // Add default caption.
    $element['default_image']['caption'] = array(
      '#type' => 'value',
      '#value' => (!empty($settings['default_image']['caption'])) ? $settings['default_image']['caption'] : $settings_default['default_image']['caption'],
    );

    return $element;
  }
}
