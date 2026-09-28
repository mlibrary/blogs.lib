<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Core\Url;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Scheduler section of the status report.
 *
 * @group scheduler
 */
#[Group('scheduler')]
#[RunTestsInSeparateProcesses]
class SchedulerStatusReportTest extends SchedulerBrowserTestBase {

  /**
   * Tests that the Scheduler Time Check report is shown.
   *
   * This is a very basic test, only checking that the report is shown and the
   * two links are rendered. It does not verify the content, timezones, etc.
   */
  public function testStatusReport() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/reports/status');

    // Check that the title is shown.
    $this->assertSession()->pageTextContains('Scheduler Time Check');

    // Check that the admin link is shown.
    $admin_regional_settings = Url::fromRoute('system.regional_settings');
    $this->assertSession()->linkExists('changed by admin users');
    $this->assertSession()->linkByHrefExists($admin_regional_settings->toString());

    // Check that the user profile link is shown.
    $account_edit = Url::fromRoute('entity.user.edit_form', ['user' => $this->adminUser->id()]);
    $this->assertSession()->linkExists('user account');
    $this->assertSession()->linkByHrefExists($account_edit->toString());
  }

}
