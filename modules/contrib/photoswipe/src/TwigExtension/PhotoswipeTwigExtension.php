<?php

namespace Drupal\photoswipe\TwigExtension;

use Drupal\Core\Render\RendererInterface;
use Drupal\photoswipe\PhotoswipeAssetsManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\ExtensionInterface;
use Twig\TwigFunction;

/**
 * Provides a Twig extension that registers various photoswipe twig extensions.
 */
class PhotoswipeTwigExtension extends AbstractExtension implements ExtensionInterface {

  /**
   * The assets manager.
   *
   * @var \Drupal\photoswipe\PhotoswipeAssetsManagerInterface
   */
  protected $photoswipeAssetManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a PhotoswipeTwigExtension object.
   *
   * @param \Drupal\photoswipe\PhotoswipeAssetsManagerInterface $asset_manager
   *   The assets manager.
   * @param \Drupal\photoswipe\PhotoswipeAssetsManagerInterface $renderer
   *   The renderer service.
   */
  public function __construct(PhotoswipeAssetsManagerInterface $asset_manager, RendererInterface $renderer) {
    $this->photoswipeAssetManager = $asset_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('attach_photoswipe', [$this, 'attachPhotoswipe']),
    ];
  }

  /**
   * Attaches the photoswipe library.
   *
   * @param array $options
   *   An array of photoswipe settings to override.
   */
  public function attachPhotoswipe($options = []) {
    $attachments = [];
    $this->photoswipeAssetManager->attach($attachments, $options);
    $this->renderer->render($attachments);
  }

}
