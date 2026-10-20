<?php

namespace Drupal\photoswipe\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Render\Element;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides formatter that supports responsive image.
 *
 * @FieldFormatter(
 *   id = "photoswipe_responsive_field_formatter",
 *   label = @Translation("Photoswipe Responsive"),
 *   field_types = {
 *     "entity_reference",
 *     "image"
 *   }
 * )
 */
class PhotoswipeResponsiveFieldFormatter extends PhotoswipeFieldFormatter {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityManager = $container->get('entity_type.manager');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach (Element::children($elements) as $child) {
      $elements[$child]['#theme'] = 'photoswipe_responsive_image_formatter';
    }

    return $elements;
  }

  /**
   * {@inheritDoc}
   */
  protected function getImageStyles() {
    $resp_image_store = $this->entityManager->getStorage('responsive_image_style');
    $responsive_image_options = [];

    $responsive_image_styles = $resp_image_store->loadMultiple();
    foreach ($responsive_image_styles as $machine_name => $responsive_image_style) {
      if ($responsive_image_style->hasImageStyleMappings()) {
        $responsive_image_options[$machine_name] = $responsive_image_style->label();
      }
    }

    return $responsive_image_options;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $resp_style_id = $this->getSetting('photoswipe_thumbnail_style');
    /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $style */
    if ($resp_style_id && $style = ResponsiveImageStyle::load($resp_style_id)) {
      // Add the responsive image style as dependency.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }

    if (!empty($this->getSetting('photoswipe_thumbnail_style_first'))) {
      $resp_style_id = $this->getSetting('photoswipe_thumbnail_style_first');
      /** @var \Drupal\responsive_image\ResponsiveImageStyleInterface $style */
      if ($resp_style_id && $style = ResponsiveImageStyle::load($resp_style_id)) {
        // Add the responsive image style as dependency.
        $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
      }
    }

    $style_id = $this->getSetting('photoswipe_image_style');
    /** @var \Drupal\image\ImageStyleInterface $style */
    if ($style_id && $style = ImageStyle::load($style_id)) {
      // If this formatter uses a valid image style to display the image, add
      // the image style configuration entity as dependency of this formatter.
      $dependencies[$style->getConfigDependencyKey()][] = $style->getConfigDependencyName();
    }
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return \Drupal::service('module_handler')->moduleExists('responsive_image') ?
      parent::isApplicable($field_definition) : FALSE;
  }

}
