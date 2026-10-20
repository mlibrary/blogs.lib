<?php

namespace Drupal\Tests\photoswipe_dynamic_caption\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\Tests\photoswipe\FunctionalJavascript\PhotoswipeJsTestBase;
use Drupal\Tests\TestFileCreationTrait;
use WebDriver\Exception\NoSuchAlert;
use WebDriver\Exception\UnexpectedAlertOpen;

/**
 * Tests the photoswipe_dynamic_caption module.
 *
 * @group photoswipe
 */
class CaptionTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait, EntityReferenceFieldCreationTrait, MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'test_page_test',
    'file',
    'image',
    'media',
    'node',
    'field_ui',
    'photoswipe',
    'photoswipe_dynamic_caption',
  ];

  /**
   * Path to the DOMPurify library directory created during a test.
   *
   * @var string|null
   */
  protected $domPurifyDirectory;

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (!empty($this->domPurifyDirectory) && is_dir($this->domPurifyDirectory)) {
      $this->removeDomPurifyDirectory($this->domPurifyDirectory);
    }
    parent::tearDown();
  }

  /**
   * Tests if caption is visible.
   */
  public function testPhotoswipeCaptionAltText() {
    $session = $this->assertSession();

    // Create the image field on the node 'article':
    $this->createImageField(
          'field_image',
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
          'photoswipe_field_formatter',
          '',
          [
            'photoswipe_dynamic_caption' => [
              'photoswipe_caption' => 'alt',
            ],
          ]
        );

    $imageAlt = 'Test alt';

    // Setup an image node:
    $fileFieldEntries = [];
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $fileFieldEntries[] = [
      'target_id' => $file->id(),
      'alt' => $imageAlt,
      'title' => 'bla',
    ];

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_image' => $fileFieldEntries,
    ]);
    $node->save();

    $this->drupalGet('/node/1');

    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    // Check if the image has an 'alt' attribute:
    $session->elementAttributeContains('css', 'a.photoswipe > img', 'alt', $imageAlt);
    // Open the photoswipe layer.
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $session->elementTextEquals('css', '.pswp__dynamic-caption', $imageAlt);
  }

  /**
   * Tests the photoswipe caption from referenced media entity field.
   */
  public function testPhotoswipeCaptionReferencedEntity() {
    $session = $this->assertSession();

    // Create an image media type.
    $this->createMediaType('image', ['id' => 'image']);

    // Create a text field on the media type for caption source.
    $this->createTextField('field_caption_source', 'media', 'image');

    // Create a media entity with caption content.
    $mediaCaptionText = 'Media entity caption text';
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();

    $media = Media::create([
      'bundle' => 'image',
      'name' => 'Test Media',
      'field_media_image' => [
        'target_id' => $file->id(),
        'alt' => 'Media alt text',
      ],
      'field_caption_source' => $mediaCaptionText,
    ]);
    $media->save();

    // Create an entity reference field on node pointing to media entities.
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_media_reference',
      'Media Reference',
      'media',
      'default',
      ['target_bundles' => ['image']],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Configure the entity reference field display to use photoswipe formatter
    // with caption from referenced media entity field.
    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = $this->container->get('entity_display.repository');
    $display_repository->getViewDisplay('node', 'article')
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
            'photoswipe_caption' => 'photoswipe_dynamic_caption_referenced_entity_field_caption_source',
          ],
        ],
      ])
      ->save();

    // Create a node with reference to the media entity.
    $node = $this->createNode([
      'title' => 'Test Node with Media Reference',
      'type' => 'article',
      'field_media_reference' => [['target_id' => $media->id()]],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());

    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    // Open the photoswipe layer.
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));

    // Verify the caption shows the text from the referenced media entity's
    // field:
    $session->elementTextEquals('css', '.pswp__dynamic-caption', $mediaCaptionText);
  }

  /**
   * Tests HTML captions with and without DOMPurify.
   */
  public function testPhotoswipeCaptionHtmlWithDomPurify() {
    $session = $this->assertSession();
    $htmlCaption = '<em>Test</em>';

    $this->createImageField(
      'field_image',
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
      'photoswipe_field_formatter',
      '',
      [
        'photoswipe_dynamic_caption' => [
          'photoswipe_caption' => 'alt',
        ],
      ]
    );

    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();

    $node = $this->createNode([
      'title' => 'Test HTML caption',
      'type' => 'article',
      'field_image' => [
        [
          'target_id' => $file->id(),
          'alt' => $htmlCaption,
        ],
      ],
    ]);
    $node->save();

    // Without DOMPurify, HTML is escaped and shown as raw text.
    $this->assertFileDoesNotExist($this->root . '/libraries/dompurify/dist/purify.min.js');
    $this->drupalGet('/node/' . $node->id());
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    // Caption should hold raw HTML:
    $session->elementTextEquals('css', '.pswp__dynamic-caption', $htmlCaption);
    $session->elementNotExists('css', '.pswp__dynamic-caption em');

    // Opt in to HTML captions, download DOMPurify, and clear library discovery
    // so it is attached.
    $this->config('photoswipe_dynamic_caption.settings')
      ->set('allowHtmlCaptions', TRUE)
      ->save();
    $this->downloadDomPurify();
    \Drupal::service('library.discovery')->clear();

    // With DOMPurify, HTML is sanitized and rendered.
    $this->drupalGet('/node/' . $node->id());
    $session->elementExists('css', 'script[src*="libraries/dompurify/dist/purify.min.js"]');
    $this->assertNotNull($session->waitForElement('css', '.photoswipe-gallery'));
    $this->getSession()->getPage()->find('css', 'a[href*="image-test.png"].photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    // Caption should hold the actual html:
    $session->elementExists('css', '.pswp__dynamic-caption em');
    $session->elementTextEquals('css', '.pswp__dynamic-caption', 'Test');
  }

  /**
   * Tests that XSS in data-overlay-title does not trigger a JavaScript alert.
   */
  public function testPhotoswipeCaptionXssDoesNotAlert() {
    $session = $this->assertSession();

    $this->config('photoswipe.settings')
      ->set('photoswipe_always_load_non_admin', TRUE)
      ->save();

    $this->drupalGet('<front>');
    $session->elementExists('css', 'script[src*="photoswipe"]');

    // Inject a photoswipe link with an XSS payload in data-overlay-title.
    $this->getSession()->executeScript(<<<'JS'
      const markup = `<a href="" class="photoswipe" data-overlay-title="&lt;img src=x onerror=alert()&gt;.">Click me</a>`;
      const container = document.createElement('div');
      container.innerHTML = markup;
      document.body.appendChild(container.firstElementChild);
      Drupal.attachBehaviors(document.body);
    JS);

    $this->assertNotNull($session->waitForElement('css', 'a.photoswipe'));

    try {
      $this->getSession()->getPage()->find('css', 'a.photoswipe')->click();
      $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
      // Give a potential onerror handler time to fire.
      $this->getSession()->wait(1000);
    }
    catch (UnexpectedAlertOpen $e) {
      $this->fail('Unexpected JavaScript alert was shown when opening Photoswipe.');
    }

    // Explicitly assert that no alert is open.
    /** @var \Behat\Mink\Driver\Selenium2Driver $driver */
    $driver = $this->getSession()->getDriver();
    try {
      $message = $driver->getWebDriverSession()->alert()->getText();
      $driver->getWebDriverSession()->alert()->accept();
      $this->fail('Unexpected JavaScript alert was shown: ' . $message);
    }
    catch (NoSuchAlert $e) {
      // Expected: caption XSS was escaped / sanitized.
    }

    // Caption should be shown as escaped raw HTML, not executed.
    $session->elementTextEquals('css', '.pswp__dynamic-caption', '<img src=x onerror=alert()>.');
  }

  /**
   * Downloads purify.min.js into the libraries directory.
   */
  protected function downloadDomPurify(): void {
    $this->domPurifyDirectory = $this->root . '/libraries/dompurify';
    $distDirectory = $this->domPurifyDirectory . '/dist';
    if (!is_dir($distDirectory)) {
      mkdir($distDirectory, 0777, TRUE);
    }

    $response = \Drupal::httpClient()->get('https://cdn.jsdelivr.net/npm/dompurify/dist/purify.min.js');
    $this->assertEquals(200, $response->getStatusCode());
    $fileData = (string) $response->getBody();
    $this->assertNotEmpty($fileData);
    file_put_contents($distDirectory . '/purify.min.js', $fileData);
    $this->assertFileExists($distDirectory . '/purify.min.js');
  }

  /**
   * Recursively removes a directory created for DOMPurify during tests.
   *
   * @param string $directory
   *   The directory to remove.
   */
  protected function removeDomPurifyDirectory(string $directory): void {
    if (!is_dir($directory)) {
      return;
    }
    $files = new \RecursiveIteratorIterator(
      new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
      \RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileInfo) {
      if ($fileInfo->isDir()) {
        rmdir($fileInfo->getPathname());
      }
      else {
        unlink($fileInfo->getPathname());
      }
    }
    rmdir($directory);
  }

  /**
   * Helper method to create a text field.
   */
  protected function createTextField($field_name, $entity_type, $bundle) {
    $entityTypeManager = $this->container->get('entity_type.manager');

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => $entity_type,
      'type' => 'string',
    ]);
    $field_storage->save();

    $field_config = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $bundle,
      'label' => 'Caption Source Field',
    ]);
    $field_config->save();

    // Set the form display.
    $form_display = $entityTypeManager
      ->getStorage('entity_form_display')
      ->load($entity_type . '.' . $bundle . '.default');
    if (!$form_display) {
      $form_display = EntityFormDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
      ]);
    }
    $form_display->setComponent($field_name, [
      'type' => 'string_textfield',
    ])->save();

    // Set the view display.
    $view_display = $entityTypeManager
      ->getStorage('entity_view_display')
      ->load($entity_type . '.' . $bundle . '.default');
    if (!$view_display) {
      $view_display = EntityViewDisplay::create([
        'targetEntityType' => $entity_type,
        'bundle' => $bundle,
        'mode' => 'default',
      ]);
    }
    $view_display->setComponent($field_name, [
      'type' => 'string',
    ])->save();
  }

  // @todo Add tests for the rest of the possible "photoswipe_caption" values
  // here.
}
