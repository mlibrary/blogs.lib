<?php

declare(strict_types=1);

namespace Drupal\Tests\photoswipe\Kernel\Controller;

use Drupal\file\Entity\File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\photoswipe\Controller\SanitizedSvgController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Tests the controller shared by the SVG Photoswipe submodules.
 *
 * @see \Drupal\photoswipe_svg_image\PhotoswipeSvgImagePreprocessProcessor
 * @see \Drupal\photoswipe_svg_image_field\PhotoswipeSvgImageFieldPreprocessProcessor
 */
#[CoversClass(SanitizedSvgController::class)]
#[Group('photoswipe')]
#[RunTestsInSeparateProcesses]
class SanitizedSvgControllerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'file', 'photoswipe'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('file');
    $this->installEntitySchema('user');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Creates a file entity and writes the given contents to its URI.
   */
  protected function createFileWithContents(string $filename, string $contents): File {
    $file = File::create(['uri' => 'public://' . $filename]);
    $file->setMimeType('image/svg+xml');
    $file->save();
    file_put_contents($file->getFileUri(), $contents);
    return $file;
  }

  /**
   * Tests that script tags and event handler attributes are stripped.
   */
  public function testMaliciousMarkupIsStripped(): void {
    $file = $this->createFileWithContents('malicious.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(2)</script><circle r="1"/></svg>');

    $response = (new SanitizedSvgController())->view($file);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('image/svg+xml', $response->headers->get('Content-Type'));
    $content = $response->getContent();
    $this->assertStringNotContainsString('<script', $content);
    $this->assertStringNotContainsString('onload', $content);
    // Sanitizing should not remove markup that isn't a threat.
    $this->assertStringContainsString('<circle', $content);
  }

  /**
   * Tests that a clean SVG is served unchanged (aside from sanitization).
   */
  public function testCleanSvgIsServed(): void {
    $file = $this->createFileWithContents('clean.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><circle r="1"/></svg>');

    $response = (new SanitizedSvgController())->view($file);

    $this->assertSame(200, $response->getStatusCode());
    $this->assertStringContainsString('<circle', $response->getContent());
    $disposition = $response->headers->get('Content-Disposition');
    $this->assertStringStartsWith('inline;', $disposition);
    $this->assertStringContainsString('clean.svg', $disposition);
  }

  /**
   * Tests that a filename containing a quote can't break the header value.
   */
  public function testFilenameWithQuoteIsEncodedSafely(): void {
    $file = $this->createFileWithContents('quote".svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

    $response = (new SanitizedSvgController())->view($file);

    $disposition = $response->headers->get('Content-Disposition');
    // A raw, unescaped quote in the filename would terminate the
    // filename="..." value early and let anything after it become a new
    // (attacker-controlled) header parameter.
    $this->assertMatchesRegularExpression('/^inline;\s*filename="[^"]*\\\\"[^"]*"/', $disposition);
  }

  /**
   * Tests that a file whose mime type isn't SVG is rejected.
   */
  public function testNonSvgMimeTypeIsRejected(): void {
    $file = $this->createFileWithContents('not-svg.svg', 'not actually svg');
    $file->setMimeType('image/png');
    $file->save();

    $this->expectException(NotFoundHttpException::class);
    (new SanitizedSvgController())->view($file);
  }

  /**
   * Tests that a file missing from the filesystem is rejected.
   */
  public function testMissingFileIsRejected(): void {
    $file = File::create(['uri' => 'public://does-not-exist.svg']);
    $file->setMimeType('image/svg+xml');
    $file->save();

    $this->expectException(NotFoundHttpException::class);
    (new SanitizedSvgController())->view($file);
  }

}
