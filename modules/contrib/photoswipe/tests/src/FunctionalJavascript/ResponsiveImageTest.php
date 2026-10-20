<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\responsive_image\Entity\ResponsiveImageStyle;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe responsive images.
 *
 * @group photoswipe
 */
class ResponsiveImageTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'responsive_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createImageField(
      'field_responsive_image',
      'node',
      'article',
      ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED],
      [],
      [],
      [
        'photoswipe_thumbnail_style_first' => '',
        'photoswipe_thumbnail_style' => '',
        'photoswipe_image_style' => '',
        'photoswipe_reference_image_field' => '',
        'photoswipe_view_mode' => '',
      ],
      'photoswipe_responsive_field_formatter',
    );
  }

  /**
   * Sets up a node containing an image field with the responsive formatter.
   *
   * @todo Move to PhotoswipeTestBase and adjust Media and Image tests.
   */
  protected function setupNodeDisplayingImage() {
    $images = $this->getTestFiles('image');
    $fileFieldEntries = [];
    for ($i = 0; $i < 3; $i++) {
      $file = File::create([
        'uri' => $images[$i]->uri,
      ]);
      $file->save();
      $fileFieldEntries[] = [
        'target_id' => $file->id(),
        'alt' => 'count-' . $i,
        'title' => '',
      ];
    }

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_responsive_image' => $fileFieldEntries,
    ]);
    $node->save();
  }

  /**
   * Creates an image style and a responsive image style using it.
   *
   * @param string $id
   *   The ID used for both the image style and the responsive image style.
   */
  protected function createResponsiveImageStyle(string $id): void {
    ImageStyle::create(['name' => $id, 'label' => $id])->save();
    ResponsiveImageStyle::create([
      'id' => $id,
      'label' => $id,
      'breakpoint_group' => 'responsive_image',
      'fallback_image_style' => $id,
      'image_style_mappings' => [
        [
          'image_mapping_type' => 'sizes',
          'image_mapping' => [
            'sizes' => '100vw',
            'sizes_image_styles' => [$id],
          ],
          'breakpoint_id' => 'responsive_image.viewport_sizing',
          'multiplier' => '1x',
        ],
      ],
    ])->save();
  }

  /**
   * Tests the responsive image formatter's first image style override.
   *
   * @see https://www.drupal.org/project/photoswipe/issues/3400251
   */
  public function testPhotoswipeResponsiveFirstOverride() {
    $session = $this->assertSession();
    $this->createResponsiveImageStyle('ps_thumbnail');
    $this->createResponsiveImageStyle('ps_first');
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.article.default')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style', 'ps_thumbnail')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style_first', 'ps_first')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementsCount('css', 'div.photoswipe-gallery a.photoswipe', 3);
    // Only the first image should use the override, the others the default:
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-0"][src*="/styles/ps_first/"]');
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-0"][src*="/styles/ps_thumbnail/"]');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-1"][src*="/styles/ps_thumbnail/"]');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-2"][src*="/styles/ps_thumbnail/"]');
  }

  /**
   * Tests the responsive image formatter's node_style "hide" option.
   */
  public function testPhotoswipeResponsiveHideOption() {
    $session = $this->assertSession();
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.article.default')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style', 'hide')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style_first', 'wide')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe');
    // We have 3 field items, so 3 anchor tags should exist:
    $session->elementsCount('css', 'div.photoswipe-gallery a.photoswipe', 3);
    // Only the first image element should exist, as the other two should be
    // unset:
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-0"]');
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-1"]');
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-2"]');

    $this->getSession()->getPage()->find('css', '.photoswipe-gallery a.photoswipe:not(.hidden)')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $session->elementExists('css', '#pswp__items img.pswp__img');
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '1 / 3');
  }

  /**
   * Tests the responsive image formatter's node_style_first "hide" option.
   */
  public function testPhotoswipeResponsiveHideFirstOption() {
    $session = $this->assertSession();
    $this->container->get('config.factory')
      ->getEditable('core.entity_view_display.node.article.default')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style', 'wide')
      ->set('content.field_responsive_image.settings.photoswipe_thumbnail_style_first', 'hide')
      ->save();

    $this->setupNodeDisplayingImage();

    $this->drupalGet('/node/1');
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe');
    // We have 3 field items, so 3 anchor tags should exist:
    $session->elementsCount('css', 'div.photoswipe-gallery a.photoswipe', 3);
    // Only the last two image element should exist, as the other one should be
    // unset:
    $session->elementNotExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-0"]');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-1"]');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe img[alt="count-2"]');

    $this->getSession()->getPage()->find('css', 'div.photoswipe-gallery > div > div:nth-child(2) > a.photoswipe:not(.hidden)')->click();
    $session->waitForElementVisible('css', '.pswp');
    $session->elementExists('css', '#pswp__items img.pswp__img');
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '2 / 3');
  }

}
