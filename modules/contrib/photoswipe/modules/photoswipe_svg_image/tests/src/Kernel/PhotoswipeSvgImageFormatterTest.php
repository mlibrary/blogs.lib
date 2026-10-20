<?php

declare(strict_types=1);

namespace Drupal\Tests\photoswipe_svg_image\Kernel;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests the 'photoswipe_svg_image_formatter' field formatter.
 */
#[Group('photoswipe')]
#[RunTestsInSeparateProcesses]
class PhotoswipeSvgImageFormatterTest extends FieldKernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'svg_image',
    'photoswipe',
    'photoswipe_svg_image',
  ];

  /**
   * The image field name used in the test.
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
    $this->installConfig(['image']);
    // File access checks 'access content' for public-scheme files.
    $this->setUpCurrentUser([], ['access content']);

    $this->fieldName = $this->randomMachineName();

    FieldStorageConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'type' => 'image',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => $this->fieldName,
      'bundle' => 'entity_test',
    ])->save();

    // Both the thumbnail and the Photoswipe modal use the 'medium' image
    // style, so we can prove SVG files bypass derivative generation while
    // other files continue to use it.
    $this->display = \Drupal::service('entity_display.repository')
      ->getViewDisplay('entity_test', 'entity_test')
      ->setComponent($this->fieldName, [
        'type' => 'photoswipe_svg_image_formatter',
        'label' => 'hidden',
        'settings' => [
          'photoswipe_thumbnail_style' => 'medium',
          'photoswipe_image_style' => 'medium',
        ],
      ]);
    $this->display->save();
  }

  /**
   * Creates a file entity and writes the given contents to its URI.
   */
  protected function createFileWithContents(string $filename, string $contents): File {
    $file = File::create(['uri' => 'public://' . $filename]);
    $file->save();
    file_put_contents($file->getFileUri(), $contents);
    return $file;
  }

  /**
   * Tests that SVG files link to the original file, not a style derivative.
   */
  #[RequiresPhpExtension('gd')]
  public function testSvgBypassesImageStyleDerivative(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $png = File::create(['uri' => 'public://test-image.png']);
    $png->save();
    // A real PNG is needed, or the GD toolkit will not consider it valid.
    $png_resource = imagecreate(300, 300);
    imagefill($png_resource, 0, 0, imagecolorallocate($png_resource, 0, 0, 0));
    imagepng($png_resource, $png->getFileUri());

    $svg = $this->createFileWithContents('test-image.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [$png, $svg],
    ]);
    $entity->save();

    $build = $this->display->build($entity);

    // The PNG is supported by the GD toolkit, so the configured image style
    // is applied as normal.
    $png_output = (string) $renderer->renderRoot($build[$this->fieldName][0]);
    $this->assertStringContainsString('styles/medium/public/test-image.png', $png_output);

    // The SVG cannot be processed by the GD toolkit, so the thumbnail must
    // not point at a (non-existent) style derivative. Instead of linking
    // directly at the stored file, which may contain untrusted markup, the
    // Photoswipe link points at the sanitizing route.
    $svg_output = (string) $renderer->renderRoot($build[$this->fieldName][1]);
    $this->assertStringNotContainsString('styles/medium/public', $svg_output);
    $this->assertMatchesRegularExpression('#<a href="[^"]*/photoswipe/svg-image/' . $svg->id() . '/sanitized"[^>]*class="[^"]*photoswipe[^"]*"#', $svg_output);
  }

  /**
   * Tests that SVG dimensions are resolved from the file when item is empty.
   */
  public function testSvgDimensionsAreResolvedFromFile(): void {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $svg = $this->createFileWithContents('test-dimensions.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="80"></svg>');

    $entity = EntityTest::create([
      'name' => $this->randomMachineName(),
      $this->fieldName => [$svg],
    ]);
    $entity->save();

    // The GD toolkit cannot read SVGs, so the field item itself carries no
    // width/height, unlike a normal uploaded raster image.
    $this->assertNull($entity->{$this->fieldName}[0]->width);
    $this->assertNull($entity->{$this->fieldName}[0]->height);

    $build = $this->display->build($entity);
    $output = (string) $renderer->renderRoot($build[$this->fieldName][0]);

    $this->assertStringContainsString('data-pswp-width="120"', $output);
    $this->assertStringContainsString('data-pswp-height="80"', $output);
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
    $svg = $this->createFileWithContents('malicious.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script></svg>');

    $path = Url::fromRoute('photoswipe_svg_image.sanitized_svg', ['file' => $svg->id()])->toString();
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
    $svg = $this->createFileWithContents('protected.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
    // Replace the 'access content' user set up in setUp() with one that
    // lacks it, so the file access handler denies the request.
    $this->setUpCurrentUser([], []);

    $path = Url::fromRoute('photoswipe_svg_image.sanitized_svg', ['file' => $svg->id()])->toString();
    $response = \Drupal::service('http_kernel')->handle(Request::create($path), HttpKernelInterface::SUB_REQUEST);

    $this->assertSame(403, $response->getStatusCode());
  }

}
