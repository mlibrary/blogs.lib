<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test that status messages are not present.
 *
 * @group reroute_email
 */
class StatusMessageTest extends RerouteEmailBrowserTestBase {

  /**
   * Test if status messages are not present on the settings page.
   */
  public function testStatusMessage(): void {
    $this->drupalGet($this->rerouteSettingsFormPath);
    $this->assertSession()->statusMessageNotExists('status');
    $this->assertSession()->statusMessageNotExists('warning');
  }

}
