<?php

namespace Drupal\photoswipe\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use enshrined\svgSanitize\Sanitizer;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves SVG files with untrusted markup stripped.
 *
 * Note: This controller is only loaded / routed in the svg submodules!
 * If not using one of the submodules, this will not be used at all.
 *
 * The core Photoswipe formatter always has an image style available, so it
 * never links directly to a stored file. The 'photoswipe_svg_image' and
 * 'photoswipe_svg_image_field' submodules are the exception: image styles
 * cannot produce a raster derivative from SVG source, so they link straight
 * at the original file instead. Serving that file as-is would bypass the
 * sanitization their respective SVG formatters apply to inline SVG output,
 * so we reapply it here.
 */
class SanitizedSvgController extends ControllerBase {

  /**
   * Returns the sanitized contents of an SVG file.
   *
   * @param \Drupal\file\FileInterface $file
   *   The SVG file to sanitize and serve.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The sanitized SVG response.
   */
  public function view(FileInterface $file): Response {
    if ($file->getMimeType() !== 'image/svg+xml') {
      throw new NotFoundHttpException();
    }

    // Ignore "E_WARNING" through "@" suppression operator and throw an error
    // instead:
    $contents = @file_get_contents($file->getFileUri());
    if ($contents === FALSE) {
      throw new NotFoundHttpException();
    }

    $sanitized = (new Sanitizer())->sanitize($contents);
    if ($sanitized === FALSE) {
      throw new NotFoundHttpException();
    }

    $response = new CacheableResponse($sanitized, 200, [
      'Content-Type' => 'image/svg+xml',
      // The stored filename is user-provided and may contain characters
      // (quotes, non-ASCII) that would break a hand-built header value, so
      // let Symfony encode it per RFC 6266 instead.
      'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $file->getFilename()),
    ]);
    $response->getCacheableMetadata()
      ->addCacheableDependency($file)
      ->addCacheTags(['file:' . $file->id()]);

    return $response;
  }

}
