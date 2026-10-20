<?php

namespace Drupal\Tests\photoswipe_dynamic_caption\Functional;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that tokens in the custom caption get replaced.
 *
 * @group photoswipe
 */
class CustomCaptionTokenTest extends BrowserTestBase {
  use TestFileCreationTrait, EntityReferenceFieldCreationTrait, MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'image',
    'media',
    'token',
    'photoswipe',
    'photoswipe_dynamic_caption',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests media, node and file tokens on a media reference field.
   */
  public function testCustomCaptionTokensOnMediaReference() {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->createMediaType('image', ['id' => 'image']);

    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Media name',
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Media alt',
        'title' => 'Media title',
      ],
    ]);
    $media->save();

    $this->createEntityReferenceField('node', 'article', 'field_media_reference', 'Media Reference', 'media', 'default', ['target_bundles' => ['image']]);
    \Drupal::service('entity_display.repository')
      ->getViewDisplay('node', 'article')
      ->setComponent('field_media_reference', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [
          'photoswipe_thumbnail_style_first' => '',
          'photoswipe_thumbnail_style' => '',
          'photoswipe_image_style' => '',
          'photoswipe_reference_image_field' => 'field_media_image',
          'photoswipe_view_mode' => '',
        ],
        'third_party_settings' => [
          'photoswipe_dynamic_caption' => [
            'photoswipe_caption' => 'custom',
            'photoswipe_caption_custom' => '[media:field_media_image:title] | [media:name] | [node:title] | [file:name]',
          ],
        ],
      ])
      ->save();

    $node = $this->createNode([
      'title' => 'Node title',
      'type' => 'article',
      'field_media_reference' => [['target_id' => $media->id()]],
    ]);

    $this->drupalGet($node->toUrl());
    $link = $this->assertSession()->elementExists('css', 'a.photoswipe');
    $this->assertSame('Media title | Media name | Node title | image-test.png', $link->getAttribute('data-overlay-title'));
  }

}
