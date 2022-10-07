<?php

namespace Drupal\Tests\notify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the Notify UI pages are reachable.
 *
 * @group notify
 */
class NotifyUITest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['notify'];

  /**
   * We use the standard profile for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Tests admin pages.
   */
  public function testAdminPages() {
    $account = $this->drupalCreateUser(['administer notify']);
    $this->drupalLogin($account);

    $this->drupalGet('admin/config/people/notify');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/people/notify/queue');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/people/notify/skip');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/people/notify/defaults');
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalGet('admin/config/people/notify/users');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests user notify pages.
   */
  public function testNotifyPages() {
    $account = $this->drupalCreateUser(['access notify']);
    $this->drupalLogin($account);

    $this->drupalGet('user/' . $account->id() . '/notify');
    $this->assertSession()->statusCodeEquals(200);
  }

}
