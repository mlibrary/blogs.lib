<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe display setting on an referenced media entity.
 *
 * @group photoswipe
 */
class MediaReferenceTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait, EntityReferenceFieldCreationTrait, MediaTypeCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'media',
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createMediaType('image', ['id' => 'image', 'new_revision' => TRUE]);
  }

  /**
   * Setup a media entity reference field on the "article" node bundle.
   */
  public function setupMediaFieldOnNodeBundle(string $fieldName = 'field_media_image', int $fieldCardinality = 1) {
    $this->createEntityReferenceField('node', 'article', $fieldName, $fieldName, 'media', 'default', ['target_bundles' => ['image']], $fieldCardinality);
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay('node', 'article')
      ->setComponent($fieldName, [
        'type' => 'entity_reference_autocomplete',
        'settings' => [],
      ])
      ->save();
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent($fieldName, [
        'type' => 'photoswipe_field_formatter',
        'settings' => [],
      ])
      ->save();
  }

  /**
   * Helper function to create a media image.
   */
  public function createMedia(?object $image = NULL, string $mediaName = 'image-test.png') {
    if ($image === NULL) {
      $image = $this->getTestFiles('image')[0];
    }
    $file = File::create([
      'uri' => $image->uri,
    ]);
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => $mediaName,
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Alt text',
        'title' => $mediaName,
      ],
    ]);
    $media->save();

    return $media;
  }

  /**
   * Tests the Photoswipe field formatter can be applied and settings persist.
   */
  public function testPhotoswipeFieldFormatterSettingsExist() {
    // Attach a media reference field using the Photoswipe formatter.
    $this->setupMediaFieldOnNodeBundle();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $displayRepository */
    $displayRepository = \Drupal::service('entity_display.repository');
    $display = $displayRepository->getViewDisplay('node', 'article');
    $component = $display->getComponent('field_media_image');

    // Confirm the field is using the Photoswipe formatter.
    $this->assertSame('photoswipe_field_formatter', $component['type']);

    // Change a formatter setting and save the display config.
    $component['settings']['photoswipe_thumbnail_style_first'] = 'large';
    $display->setComponent('field_media_image', $component)->save();

    // Reload the display and verify the setting was stored.
    $component = $displayRepository->getViewDisplay('node', 'article')
      ->getComponent('field_media_image');
    $this->assertSame('large', $component['settings']['photoswipe_thumbnail_style_first']);
  }

  /**
   * Tests the photoswipe formatter on node display.
   */
  public function testPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle();
    $media = $this->createMedia();
    // Create the node with a test file uploaded:
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests if the access permissions work correctly for an anonymous user.
   */
  public function testPhotoswipeFieldFormatterNodeDisplayPermissionAnonymous() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle();
    $media = $this->createMedia();
    // Create the node with a test file uploaded:
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Unpublish media_entity:
    $media->setUnpublished()->save();
    // Logout:
    $this->drupalLogout();
    // Check if image is not rendered anymore:
    $session->elementNotExists('css', 'img[src*="image-test.png"]');
    $session->elementNotExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementNotExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  }

  /**
   * Tests if the access permissions are correct for an authenticated user.
   */
  public function testPhotoswipeFieldFormatterNodeDisplayPermissionAuthenticated() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle();
    $media = $this->createMedia();
    // Create the node with a test file uploaded:
    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the image is loaded with the correct defaults and wrappers:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Unpublish media_entity:
    $media->setUnpublished()->save();
    // Logout and login as authenticated user:
    $this->drupalLogout();
    $this->drupalLogin($this->user);
    // Check if image is not rendered anymore:
    $session->elementNotExists('css', 'img[src*="image-test.png"]');
    $session->elementNotExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementNotExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
  }

  /**
   * Tests the photoswipe formatter on node display with two media fields.
   */
  public function testTwoPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle('field_media_image');
    $this->setupMediaFieldOnNodeBundle('field_media_image_two');

    $media = $this->createMedia();
    $media2 = $this->createMedia();

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        'target_id' => $media->id(),
      ],
      'field_media_image_two' => [
        'target_id' => $media2->id(),
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Check that two of each element exist:
    $session->elementsCount('css', '.photoswipe-gallery', 2);
    $session->elementsCount('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe', 2);
    $session->elementsCount('css', '.photoswipe-gallery a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]', 2);
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests upload of multiple media images on one field.
   */
  public function testMultipleImagesOnFieldWithPhotoswipeFieldFormatter() {
    $session = $this->assertSession();

    $this->setupMediaFieldOnNodeBundle('field_media_image', FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $media = $this->createMedia();
    $media2 = $this->createMedia($this->getTestFiles('image')[1], 'image-test2');

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_media_image' => [
        0 => [
          'target_id' => $media->id(),
        ],
        1 => [
          'target_id' => $media2->id(),
        ],
      ],
    ]);
    $node->save();
    $this->drupalGet('/node/' . $node->id());
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the first picture:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe');

    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the first picture:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', 'div.photoswipe-gallery div > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the second picture:
    // Check, that only one photoswipe-gallery wrapper exists:
    $session->elementsCount('css', 'div.photoswipe-gallery', 1);
    // Check that two of each element exist:
    $session->elementsCount('css', 'div.photoswipe-gallery div > a.photoswipe', 2);
    // @todo Check the photoswipe functionalities here.
  }

}
