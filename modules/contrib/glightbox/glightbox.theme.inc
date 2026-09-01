<?php

/**
 * @file
 * GLightbox theme functions.
 */

use Drupal\Component\Utility\Crypt;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;

/**
 * Prepares variables for glightbox formatter templates.
 *
 * Default template: glightbox-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - entity: An entity object.
 *   - settings: Formatter settings array.
 *
 * @codingStandardsIgnoreStart
 */
function template_preprocess_glightbox_formatter(&$variables) {
  // @codingStandardsIgnoreEnd
  $formatter_service = \Drupal::service('glightbox.formatter');
  $item = $variables['item'];
  $item_attributes = $variables['item_attributes'] ?? [];
  $entity = $variables['entity'];
  $settings = $variables['settings'];
  $image_uri = $item->entity->getFileUri();
  $classes_array = ['glightbox'];

  $parent_entity_id = '';
  $parent_paragraph_id = '';
  if (!empty($entity->_referringItem)) {
    $parent = $entity->_referringItem->getEntity();
    if ($parent instanceof EntityInterface) {
      $parent_entity_id = $parent->id();
    }

    if ($parent->getEntityTypeId() == 'paragraph') {
      $paragraph_parent = $parent->getParentEntity();
      if ($paragraph_parent instanceof EntityInterface) {
        $parent_paragraph_id = $paragraph_parent->id();
      }
    }
  }

  $gallery_bubbleable_metadata = new BubbleableMetadata();
  $gallery_id = \Drupal::service('glightbox.gallery_id_generator')->generateId($entity, $item, $settings, $parent_entity_id, $parent_paragraph_id, $gallery_bubbleable_metadata);
  $gallery_bubbleable_metadata->applyTo($variables);

  // Set up the $variables['image'] parameter.
  if ($settings['style_name'] == 'hide') {
    $variables['image'] = [];
    $classes_array[] = 'js-hide';
  }
  elseif (!empty($settings['style_name'])) {
    $variables['image'] = [
      '#theme' => 'image_style',
      '#style_name' => $settings['style_name'],
    ];
  }
  else {
    $variables['image'] = [
      '#theme' => 'image',
    ];
  }

  if (!empty($variables['image'])) {
    $variables['image']['#attributes'] = $item_attributes;

    // Do not output an empty 'title' attribute.
    if (!empty($item->title)) {
      $variables['image']['#title'] = $item->title;
    }

    foreach (['width', 'height', 'alt'] as $key) {
      $variables['image']["#$key"] = $item->$key;
    }

    $variables['attributes']['data-alt'] = $item->alt;
    $variables['image']['#uri'] = empty($item->uri) ? $image_uri : $item->uri;
  }

  if (!empty($settings['glightbox_image_style'])) {
    $style = ImageStyle::load($settings['glightbox_image_style']);
    $variables['url'] = $style->buildUrl($image_uri);
  }
  else {
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    $variables['url'] = $file_url_generator->generateAbsoluteString($image_uri);
  }

  $caption = $formatter_service->getCaption($variables);
  if (!empty($caption)) {
    $variables['attributes']['title'] = $caption;
    $variables['attributes']['data-glightbox'] = "title: $caption";
  }

  $variables['attributes']['data-gallery'] = $gallery_id;
  $variables['attributes']['class'] = $classes_array;

  if ($caption_description = $formatter_service->getCaption($variables, 'glightbox_caption_description', FALSE)) {
    $variables['caption_description'] = $caption_description;
    $variables['attributes']['data-glightbox'] = "description: $caption_description";
  }
}

/**
 * Prepares variables for glightbox responsive formatter templates.
 *
 * Default template: glightbox-responsive-formatter.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - entity: An entity object.
 *   - settings: Formatter settings array.
 */
function template_preprocess_glightbox_responsive_formatter(array &$variables) {
  $glightbox_inline = \Drupal::moduleHandler()->moduleExists('glightbox_inline');
  $formatter_service = \Drupal::service('glightbox.formatter');

  $item = $variables['item'];
  $item_attributes = $variables['item_attributes'] ?? [];
  $entity = $variables['entity'];
  $entity_bundle = $entity->bundle();
  $id = $entity->id();
  $entity_id = !empty($id) ? $entity_bundle . '-' . $id : 'entity-id';

  $settings = $variables['settings'];
  $image_uri = $item->entity->getFileUri();
  $classes_array = ['glightbox'];

  $responsive_style = NULL;
  if (!empty($settings['glightbox_responsive_node_style'])) {
    $responsive_style = ResponsiveImageStyle::load($settings['glightbox_responsive_node_style']);
  }

  // Set up the $variables['responsive_image'] parameter.
  if ($settings['glightbox_responsive_node_style'] == 'hide') {
    $variables['responsive_image'] = [];
    $classes_array[] = 'js-hide';
  }
  elseif ($responsive_style) {
    $variables['responsive_image'] = [
      '#theme' => 'responsive_image',
      '#responsive_image_style_id' => $settings['glightbox_responsive_node_style'],
    ];
  }
  else {
    $variables['responsive_image'] = [
      '#theme' => 'image',
    ];
  }

  if (!empty($variables['responsive_image'])) {
    $attributes = [];
    // Do not output an empty 'title' attribute.
    if (mb_strlen($item->title ?? '') != 0) {
      $variables['responsive_image']['#title'] = $item->title;
      $attributes['title'] = $item->title;
    }

    foreach (['width', 'height', 'alt'] as $key) {
      $variables['responsive_image']["#$key"] = $item->$key;
    }
    $attributes['alt'] = $item->alt;
    $variables['attributes']['data-alt'] = $item->alt;

    $variables['responsive_image']['#uri'] = empty($item->uri) ? $image_uri : $item->uri;
    $variables['responsive_image']['#attributes'] = $attributes + $item_attributes;
  }

  $responsive_style = NULL;
  if (!empty($settings['glightbox_responsive_image_style'])) {
    $responsive_style = ResponsiveImageStyle::load($settings['glightbox_responsive_image_style']);
  }

  // Since responsive images don't have an external url, link to internal
  // content.
  if ($glightbox_inline && $responsive_style) {
    // Create a unique internal link for the picture tag.
    // We use a short token since randomness is not critical.
    $image_id = $entity_id . '-' . Crypt::randomBytesBase64(8);
    $variables['image_id'] = $image_id;
    $variables['url'] = '';
    $variables['responsive_image'] = [
      '#theme' => 'responsive_image',
      '#responsive_image_style_id' => $settings['glightbox_responsive_image_style'],
      '#uri' => empty($item->uri) ? $image_uri : $item->uri,
      '#width' => $item->width,
      '#height' => $item->height,
    ];
    $variables['attributes']['data-glightbox-inline'] = '#' . $image_id;
  }
  if (!empty($settings['glightbox_image_style'])) {
    $style = ImageStyle::load($settings['glightbox_image_style']);
    $variables['url'] = $style->buildUrl($image_uri);
  }
  else {
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');

    $variables['url'] = $file_url_generator->generateAbsoluteString($image_uri);
  }

  $gallery_bubbleable_metadata = new BubbleableMetadata();
  $gallery_id = \Drupal::service('glightbox.gallery_id_generator')->generateId($entity, $item, $settings, '', '', $gallery_bubbleable_metadata);
  $gallery_bubbleable_metadata->applyTo($variables);

  $caption = $formatter_service->getCaption($variables);
  if (!empty($caption)) {
    $variables['attributes']['title'] = $caption;
    $variables['attributes']['data-glightbox'] = "title: $caption";
  }
  $variables['attributes']['data-gallery'] = $gallery_id;
  $variables['attributes']['class'] = $classes_array;

  if ($caption_description = $formatter_service->getCaption($variables, 'glightbox_caption_description', FALSE)) {
    $variables['caption_description'] = $caption_description;
    $variables['attributes']['data-glightbox'] = "description: $caption_description";
  }
}
