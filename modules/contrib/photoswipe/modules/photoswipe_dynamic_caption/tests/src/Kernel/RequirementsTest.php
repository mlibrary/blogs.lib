<?php

namespace Drupal\Tests\photoswipe_dynamic_caption\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests photoswipe_dynamic_caption_requirements().
 */
#[Group('photoswipe')]
#[RunTestsInSeparateProcesses]
class RequirementsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['photoswipe', 'photoswipe_dynamic_caption'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['photoswipe']);
    require_once $this->root . '/core/includes/install.inc';
    \Drupal::moduleHandler()->loadInclude('photoswipe_dynamic_caption', 'install');
  }

  /**
   * Ensure, that enabling cdn, simply returns an empty array (no error report).
   *
   * Regression test for issue #3553643.
   */
  public function testCdnEnabledWithoutLocalLibrary(): void {
    \Drupal::configFactory()
      ->getEditable('photoswipe.settings')
      ->set('enable_cdn', TRUE)
      ->save();

    $requirements = photoswipe_dynamic_caption_requirements('runtime');
    // The cdn is enabled, the requirements should simply return an empty array:
    $this->assertEmpty($requirements);
  }

  /**
   * Ensures the "no library, CDN off" error path still reports an error.
   *
   * Regression test for issue #3553643.
   */
  public function testCdnDisabledWithoutLocalLibrary(): void {
    \Drupal::configFactory()
      ->getEditable('photoswipe.settings')
      ->set('enable_cdn', FALSE)
      ->save();

    $requirements = photoswipe_dynamic_caption_requirements('runtime');

    $this->assertArrayHasKey('photoswipe_dynamic_caption', $requirements);
    $this->assertSame(REQUIREMENT_ERROR, $requirements['photoswipe_dynamic_caption']['severity']);
  }

}
