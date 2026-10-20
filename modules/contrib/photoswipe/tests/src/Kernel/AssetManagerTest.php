<?php

namespace Drupal\Tests\photoswipe\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests asset manager.
 *
 * @coversClass \Drupal\photoswipe\PhotoswipeAssetsManager
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class AssetManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'photoswipe_assets_test', 'photoswipe'];

  /**
   * Asset manager.
   *
   * @var \Drupal\photoswipe\PhotoswipeAssetsManager
   */
  protected $assetManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['photoswipe']);
    $this->assetManager = \Drupal::service('photoswipe.assets_manager');
  }

  /**
   * Test photoswipe_js_options hook for themes and modules.
   */
  public function testPhotoswipeJsOptionsHook() {
    // Active test theme.
    \Drupal::service('theme_installer')->install(['photoswipe_test_theme']);
    // Theme installation rebuilds the container; refresh the service.
    $this->assetManager = \Drupal::service('photoswipe.assets_manager');
    $this->config('system.theme')->set('default', 'photoswipe_test_theme')->save();
    /** @var \Drupal\Core\Theme\ThemeManagerInterface $manager */
    $manager = \Drupal::service('theme.manager');
    $manager->setActiveTheme(\Drupal::service('theme.initialization')->initTheme('photoswipe_test_theme'));

    $dummy = [
      '#markup' => 'dummy',
    ];
    $this->assetManager->attach($dummy);

    $options = $dummy['#attached']['drupalSettings']['photoswipe']['options'];

    $this->assertEquals(FALSE, (bool) $options['loop']);

    $this->assertEquals('Test', $options['errorMsg']);
  }

}
