<?php

declare(strict_types=1);

namespace Drupal\Tests\photoswipe_svg_image_field\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the 'photoswipe_svg_image_field_formatter' field formatter.
 */
#[Group('photoswipe')]
#[RunTestsInSeparateProcesses]
class PhotoswipeSvgImageFieldFormatterTest extends FieldKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'svg_image_field',
    'photoswipe',
    'photoswipe_svg_image_field',
  ];

  /**
   * The svg_image_field field name used in the test.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The entity view display used in the test.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    // File access checks 'access content' for public-scheme files.
    $this->setUpCurrentUser([], ['access content']);

    $this->fieldName = $this->randomMachineName();

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'type' => 'svg_image_field',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ])->save();

    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($this->fieldName, [
        'type' => 'photoswipe_svg_image_field_formatter',
        'label' => 'hidden',
        'settings' => [
          'photoswipe_thumbnail_width' => 50,
          'photoswipe_thumbnail_height' => 60,
        ],
      ]);
    $this->display->save();
  }

  /**
   * Creates an SVG file entity and writes the given contents to its URI.
   */
  protected function createSvgFile(string $filename, string $contents): File {
    $file = File::create(['uri' => 'public://' . $filename]);
    $file->save();
    file_put_contents($file->getFileUri(), $contents);
    return $file;
  }

  /**
   * Tests the rendered markup for a single SVG Image Field item.
   */
  public function testFormatterRendersSvgWithPhotoswipeMarkup(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('field-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [
        ['target_id' => $svg->id(), 'alt' => 'An SVG image', 'title' => 'SVG title'],
      ],
    ]);
    $entity->save();

    $build = $this->display->build($entity);
    $output = (string) $renderer->renderRoot($build[$this->fieldName][0]);

    // This field type has no image style support to build a derivative
    // from, but the lightbox link still must not point directly at the
    // stored file, which may contain untrusted markup from the upload; it
    // points at the sanitizing route instead.
    $this->assertMatchesRegularExpression('#<a href="[^"]*/photoswipe/svg-image-field/' . $svg->id() . '/sanitized"[^>]*class="[^"]*photoswipe[^"]*"#', $output);
    // The intrinsic SVG dimensions (not the thumbnail size) drive the
    // Photoswipe zoom target.
    $this->assertStringContainsString('data-pswp-width="200"', $output);
    $this->assertStringContainsString('data-pswp-height="150"', $output);
    // The thumbnail <img> uses the configured thumbnail dimensions instead.
    $this->assertMatchesRegularExpression('#<img[^>]*width="50"[^>]*height="60"#', $output);
    $this->assertStringContainsString('alt="An SVG image"', $output);
    $this->assertStringContainsString('title="SVG title"', $output);
  }

  /**
   * Tests that dimensions fall back to the viewBox when width/height absent.
   */
  public function testDimensionsFallBackToViewBox(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('viewbox-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 32"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $svg->id()]],
    ]);
    $entity->save();

    $build = $this->display->build($entity);
    $output = (string) $renderer->renderRoot($build[$this->fieldName][0]);

    $this->assertStringContainsString('data-pswp-width="64"', $output);
    $this->assertStringContainsString('data-pswp-height="32"', $output);
  }

  /**
   * Tests that the field wrapper gets the photoswipe-gallery class.
   */
  public function testGalleryWrapperClass(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createSvgFile('wrapper-test.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [['target_id' => $svg->id()]],
    ]);
    $entity->save();

    $build = $this->display->build($entity);
    $field_output = (string) $renderer->renderRoot($build[$this->fieldName]);
    $this->assertStringContainsString('photoswipe-gallery', $field_output);

    // Disabling the setting removes the wrapper class again.
    $this->display->setComponent($this->fieldName, [
      'type' => 'photoswipe_svg_image_field_formatter',
      'label' => 'hidden',
      'settings' => [
        'photoswipe_remove_gallery_wrapper_class' => TRUE,
      ],
    ])->save();

    $build = $this->display->build($entity);
    $field_output = (string) $renderer->renderRoot($build[$this->fieldName]);
    $this->assertStringNotContainsString('photoswipe-gallery', $field_output);
  }

  /**
   * Tests that the route Photoswipe links to strips untrusted SVG markup.
   *
   * The sanitizer's own behavior is covered by
   * \Drupal\Tests\photoswipe\Kernel\Controller\SanitizedSvgControllerTest;
   * this only needs to prove the route this module registers actually
   * reaches that controller.
   */
  public function testSanitizedSvgRouteStripsScripts(): void {
    $svg = $this->createSvgFile('malicious.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script></svg>');

    $path = Url::fromRoute('photoswipe_svg_image_field.sanitized_svg', ['file' => $svg->id()])->toString();
    $response = \Drupal::service('http_kernel')->handle(Request::create($path), HttpKernelInterface::SUB_REQUEST);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    $content = $response->getContent();
    $this->assertStringNotContainsString('<script', $content);
    $this->assertStringNotContainsString('onload', $content);
  }

  /**
   * Tests that the sanitizing route enforces file view access.
   */
  public function testSanitizedSvgRouteDeniesAccessWithoutPermission(): void {
    $svg = $this->createSvgFile('protected.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    // Replace the 'access content' user set up in setUp() with one that
    // lacks it, so the file access handler denies the request.
    $this->setUpCurrentUser([], []);

    $path = Url::fromRoute('photoswipe_svg_image_field.sanitized_svg', ['file' => $svg->id()])->toString();
    $response = \Drupal::service('http_kernel')->handle(Request::create($path), HttpKernelInterface::SUB_REQUEST);

    $this->assertSame(403, $response->getStatusCode());
  }

}
