<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe display setting on an image file.
 *
 * @todo Refactor these tests based on the MediaReferenceTest refactor.
 *
 * @group photoswipe
 */
class ImageFileTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field_ui',
  ];

  // @codingStandardsIgnoreStart
  // /**
  //  * Tests if the Photoswipe field formatter settings exist.
  //  */
  // public function testPhotoswipeFieldFormatterSettingsExist() {
  //   $session = $this->assertSession();
  //   $page = $this->getSession()->getPage();
  // $field_settings = ['alt_field_required' => 0];
  //   $this->createImageField('field_test', 'node', 'article', [
  //     'uri_scheme' => 'public',
  //     'required' => 'true',
  //   ], $field_settings);
  //   // Go to manage display page.
  //   $this->drupalGet('admin/structure/types/manage/article/display');
  //   $session->pageTextContains('Photoswipe');
  //   // Check if the photoswipe field formatter is selected:
  //   $session->elementAttributeContains('css', '#edit-fields-field-test-type > option[value="photoswipe_field_formatter"]', 'selected', 'selected');
  //   // Check if all formatter settings exist, and have the correct default
  //   // selected setting:
  //   $page->pressButton('edit-fields-field-test-settings-edit');
  //   $session->waitForElementVisible('css', 'select[id*=edit-fields-field-test-settings-edit-form-settings-photoswipe-thumbnail-style-first]');
  //   $session->elementExists('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-thumbnail-style-first"]');
  //   $session->elementExists('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-thumbnail-style"]:not([id*=first])');
  //   $session->elementExists('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-image-style"]');
  //   $session->elementTextEquals('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-thumbnail-style-first"] > option[selected="selected"]', 'No special style.');
  //   $session->elementTextEquals('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-thumbnail-style"]:not([id*=first]) > option[selected="selected"]', 'None (original image)');
  //   $session->elementTextEquals('css', 'select[id*="edit-fields-field-test-settings-edit-form-settings-photoswipe-image-style"] > option[selected="selected"]', 'None (original image)');
  //   // @todo Why does the following select not have a selected field?:
  //   // $session->elementTextEquals('css', 'select[id*=edit-fields-field-test-settings-edit-form-settings-photoswipe-caption] > option[selected="selected"]', 'Image title tag');
  //   // Check if changing a setting and submitting the display,
  //   // won't break anything:
  //   $page->selectFieldOption('fields[field_test][settings_edit_form][settings][photoswipe_thumbnail_style_first]', 'large');
  //   $page->pressButton('Update');
  //   $page->pressButton('edit-submit');
  //   $session->pageTextContains('Your settings have been saved.');
  // }
  // @codingStandardsIgnoreEnd

  /**
   * Checks the photoswipe library.
   */
  public function testLibrary() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 1];
    $this->createImageField('field_test', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $this->assertNotNull($session->waitForElementVisible('css', '.image-widget > img'));
    $session->pageTextContains('Alternative text');
    $page->fillField('Alternative text', 'Alt text');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
    $this->validateCdnLibraries($session);
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
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
  }

  /**
   * Tests the photoswipe formatter on node display.
   */
  public function testPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 1];
    $this->createImageField('field_test', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $this->assertNotNull($session->waitForElementVisible('css', '.image-widget > img'));
    $session->pageTextContains('Alternative text');
    $page->fillField('Alternative text', 'Alt text');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
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
   * Tests the photoswipe formatter settings on node display.
   *
   * @todo We should add this test to the MediaReferenceTest in the future.
   */
  public function testPhotoswipeFieldFormatterSettingsOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 1];
    $this->createImageField(
      name: 'field_test',
      storage_settings: [
        'uri_scheme' => 'public',
        'required' => 'true',
      ],
      field_settings: $field_settings,
      formatter_settings: [
        // @todo To test this setting, we need to have a responsive image style:
        // 'photoswipe_thumbnail_style' => 'large',
        // @todo These settings can only be checked inside the photoswipe
        // 'modal' view. Check them in the future as well:
        // 'photoswipe_image_style' => 'large',
        // 'photoswipe_thumbnail_style_first' => 'large',
        'image_loading' => [
          'attribute' => 'eager',
        ],
      ],
    );
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $this->assertNotNull($session->waitForElementVisible('css', '.image-widget > img'));
    $session->pageTextContains('Alternative text');
    $page->fillField('Alternative text', 'Alt text');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
    $this->validateCdnLibraries($session);
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'img[src*="image-test.png"]');
    // Check if loading "eager" is set:
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'loading', 'eager');

    // Now change the loading attribute to "lazy":
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_test', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [
          'image_loading' => [
            'attribute' => 'lazy',
          ],
        ],
        'third_party_settings' => [],
      ])
      ->save();

    // Reload the page, and check if the loading attribute is set to "lazy":
    $this->drupalGet('/node/1');
    $this->validateCdnLibraries($session);
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'img[src*="image-test.png"]');
    // Check if loading "eager" is set:
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'loading', 'lazy');
  }

  /**
   * Tests the photoswipe formatter on node display with two image fields.
   */
  public function testTwoPhotoswipeFieldFormatterOnNodeDisplay() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 0];
    $this->createImageField('field_test', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    $this->createImageField('field_test2', 'node', 'article', [
      'uri_scheme' => 'public',
      'required' => 'true',
    ], $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $this->assertNotEmpty($image_upload_field_2 = $page->find('css', '#edit-field-test2-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_realpath = $this->container->get('file_system')->realpath($image->uri);
    $image_upload_field->attachFile($image_realpath);
    // Wait 2 seconds between attaching files, this is necessary, because
    // otherwise, the second file will not get attached, see
    // https://www.drupal.org/project/drupal/issues/3272424
    $page->waitFor(2, function () {
      return FALSE;
    });
    $image_upload_field_2->attachFile($image_realpath);
    $this->assertNotEmpty($session->waitForElementVisible('css', 'input[id*="edit-field-test-0-alt"]'));
    $this->assertNotEmpty($session->waitForElementVisible('css', 'input[id*="edit-field-test2-0-alt"]'));
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
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
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the second picture:
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test_0.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test_0.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the second picture:
    $session->elementExists('css', 'img[src*="image-test_0.png"]');
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    $session->elementExists('css', '.photoswipe-gallery a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'height', '20');
  }

  /**
   * Tests upload of multiple images on one field.
   */
  public function testMultipleImagesOnFieldWithPhotoswipeFieldFormatter() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 0];
    $storage_settings = [
      'cardinality' => -1,
      'uri_scheme' => 'public',
      'required' => 'true',
    ];
    $this->createImageField('field_test', 'node', 'article', $storage_settings, $field_settings);
    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_realpath = $this->container->get('file_system')->realpath($image->uri);
    $image_upload_field->attachFile($image_realpath);
    $session->waitForElementVisible('css', 'input[id*="edit-field-test-1-upload"]');
    $this->assertNotEmpty($image_upload_field2 = $page->find('css', 'input[id*="edit-field-test-1-upload"]'));
    $image_upload_field2->attachFile($image_realpath);
    $session->waitForElementVisible('css', 'input[id*="edit-field-field-test-1-alt"]');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");
    $this->drupalGet('/node/1');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the first picture:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery div > a[href*="image-test.png"].photoswipe');

    $session->elementAttributeExists('css', 'a[href*="image-test.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the first picture:
    $session->elementExists('css', 'img[src*="image-test.png"]');
    $session->elementExists('css', 'a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementExists('css', '.photoswipe-gallery div > a[href*="image-test.png"].photoswipe > img[src*="image-test.png"]');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test.png"]', 'height', '20');
    // Check if the anker element is set with the correct classes, wrappers and
    // attributes for the second picture:
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe');
    $session->elementExists('css', '.photoswipe-gallery div> a[href*="image-test_0.png"].photoswipe');
    $session->elementAttributeExists('css', 'a[href*="image-test_0.png"].photoswipe', 'data-pswp-width');
    // Check if the image is loaded with the correct defaults and wrappers for
    // the second picture:
    $session->elementExists('css', 'img[src*="image-test_0.png"]');
    $session->elementExists('css', 'a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    $session->elementExists('css', '.photoswipe-gallery div> a[href*="image-test_0.png"].photoswipe > img[src*="image-test_0.png"]');
    // Uploaded pictures are not broken during testing, but only on later
    // inspection. See https://www.drupal.org/project/drupal/issues/3272192.
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'width', '40');
    $session->elementAttributeContains('css', 'img[src*="image-test_0.png"]', 'height', '20');
    // @todo Check the photoswipe functionalities here.
  }

  /**
   * Tests the responsive photoswipe formatter on node display.
   *
   * @todo Implement this!
   */
  public function todoTestResponsivePhotoswipeFieldFormatterOnNodeDisplay() {
  }

  /**
   * Tests the "Remove photoswipe gallery wrapper class" setting.
   */
  public function testPhotoswipeFieldFormatterRemoveWrapperClass() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $field_settings = ['alt_field_required' => 1];
    $this->createImageField(
      name: 'field_test',
      storage_settings: [
        'uri_scheme' => 'public',
        'required' => 'true',
      ],
      field_settings: $field_settings,
      formatter_settings: [
        'photoswipe_remove_gallery_wrapper_class' => TRUE,
      ],
    );

    // Create the node with a test file uploaded:
    $this->drupalGet('node/add/article');
    $title = 'My test content';
    $page->fillField('title[0][value]', $title);
    $this->assertNotEmpty($image_upload_field = $page->find('css', '#edit-field-test-0-upload'));
    $image = $this->getTestFiles('image')[0];
    $image_upload_field->attachFile($this->container->get('file_system')->realpath($image->uri));
    $this->assertNotNull($session->waitForElementVisible('css', '.image-widget > img'));
    $session->pageTextContains('Alternative text');
    $page->fillField('Alternative text', 'Alt text');
    $page->pressButton('edit-submit');
    $session->pageTextContains("Article {$title} has been created.");

    $this->drupalGet('/node/1');
    $this->validateCdnLibraries($session);

    // Verify that the fallback wrapper is set by prepare-galleries.js:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery--fallback-wrapper'));
    $session->elementExists('css', 'span.photoswipe-gallery--fallback-wrapper a[href*="image-test.png"].photoswipe');

    // Now, let's change the setting back to FALSE (do not remove wrapper class)
    // and verify that the fallback wrapper is NOT used, and the standard
    // photoswipe-gallery is used.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('field_test', [
        'type' => 'photoswipe_field_formatter',
        'settings' => [
          'photoswipe_remove_gallery_wrapper_class' => FALSE,
        ],
        'third_party_settings' => [],
      ])
      ->save();

    $this->drupalGet('/node/1');
    $this->validateCdnLibraries($session);

    // The standard photoswipe-gallery wrapper should be present on the field
    // wrapper:
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    // The fallback wrapper should NOT be present:
    $session->elementNotExists('css', '.photoswipe-gallery--fallback-wrapper');
  }

}
