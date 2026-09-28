<?php

declare(strict_types=1);

namespace Drupal\Tests\glightbox\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Test glightbox.
 *
 * @group glightbox
 */
#[Group('glightbox')]
#[RunTestsInSeparateProcesses]
final class GlightboxTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['glightbox'];

  /**
   * Test installation and setup of module.
   */
  public function testGlightbox(): void {
    $admin_user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin_user);
    $this->drupalGet(Url::fromRoute('glightbox.admin_settings'));
    $this->assertSession()->pageTextContains('GLightbox settings');
  }

}
