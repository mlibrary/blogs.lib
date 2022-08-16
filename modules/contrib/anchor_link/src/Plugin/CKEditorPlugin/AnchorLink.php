<?php

namespace Drupal\anchor_link\Plugin\CKEditorPlugin;

use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginBase;

/**
 * Defines the "link" plugin.
 *
 * @CKEditorPlugin(
 *   id = "link",
 *   label = @Translation("CKEditor Web link"),
 *   module = "anchor_link"
 * )
 */
class AnchorLink extends CKEditorPluginBase {

  /**
   * Implements \Drupal\ckeditor\Plugin\CKEditorPluginInterface::getFile().
   */
  public function getFile() {
    return drupal_get_path('module', 'anchor_link') . '/js/plugins/link/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return [
      'fakeobjects',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isInternal() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [
      'LinkToAnchor' => [
        'label' => $this->t('Link to anchor'),
        'image' => drupal_get_path('module', 'anchor_link') . '/js/plugins/link/icons/link.png',
      ],
      'UnlinkAnchor' => [
        'label' => $this->t('Unlink Anchor'),
        'image' => drupal_get_path('module', 'anchor_link') . '/js/plugins/link/icons/unlink.png',
      ],
      'Anchor' => [
        'label' => $this->t('Anchor'),
        'image' => drupal_get_path('module', 'anchor_link') . '/js/plugins/link/icons/anchor.png',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

}
