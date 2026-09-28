<?php

namespace Drupal\Tests\reroute_email_symfony_mailer\Functional;

use Drupal\Tests\reroute_email\Functional\RerouteEmailBrowserTestBase;

/**
 * Test that status messages are present.
 *
 * @group reroute_email_symfony_mailer
 */
class StatusMessageTest extends RerouteEmailBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['symfony_mailer'];

  /**
   * Test if status messages with warning are present.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testStatusMessageExists(): void {
    $this->drupalGet($this->rerouteSettingsFormPath);
    $this->assertSession()->statusMessageNotExists('status');
    $this->assertSession()->statusMessageExists('warning');
    $this->assertSession()->pageTextContains('Drupal Symfony Mailer module is enabled. "Reroute Email (Symfony Mailer support)" module must be enabled for the proper rerouting of emails.');

    \Drupal::service('module_installer')->install(['reroute_email_symfony_mailer']);
    $this->rebuildAll();

    $this->drupalGet($this->rerouteSettingsFormPath);
    $this->assertSession()->statusMessageNotExists('warning');
    $this->assertSession()->statusMessageExists('status');
    $this->assertSession()->pageTextContains('Drupal Symfony Mailer module is enabled. Those steps must be ensured to reroute emails properly:');
  }

}
