<?php

namespace Drupal\Tests\mimemail\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests operation of the Mime Mail settings page.
 *
 * @group mimemail
 */
class MimeMailSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['mimemail', 'field', 'help', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unprivUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // System help block is needed to see output from hook_help().
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);

    // Create our test users.
    $this->adminUser = $this->createUser([
      'administer site configuration',
      'access administration pages',
    ]);
    $this->unprivUser = $this->createUser();
  }

  /**
   * Tests module permissions / access to configuration page.
   */
  public function testUserAccess(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test as anonymous user.
    $this->drupalGet('admin/config/system/mimemail');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    // Test as authenticated but unprivileged user.
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('admin/config/system/mimemail');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    // Test as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/system/mimemail');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Configure Mime Mail');
    $this->drupalLogout();
  }

}
