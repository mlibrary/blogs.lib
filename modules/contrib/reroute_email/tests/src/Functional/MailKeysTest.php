<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;

/**
 * Test Reroute Email with mail keys filter.
 *
 * @group reroute_email
 */
class MailKeysTest extends RerouteEmailBrowserTestBase {

  /**
   * Test Reroute Email with mail keys filter.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testMailKeysSettings(): void {
    // Configure to reroute all outgoing emails.
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => TRUE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => static::$rerouteDestination,
    ]);
    $this->assertMailReroutedFromTestForm(['to' => static::$originalDestination]);

    // Configure to NOT reroute all outgoing emails (not existed mail key).
    $this->configureRerouteEmail([RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS => 'not_existed_module']);
    $this->assertMailNotReroutedFromTestForm(['to' => static::$originalDestination]);
    $this->assertMailHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'MAILKEY-ALLOWED');

    // Configure to reroute emails from our test form.
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS => 'reroute_email_test_email_form',
    ]);
    $this->assertMailReroutedFromTestForm(['to' => static::$originalDestination]);

    // Configure to reroute all outgoing emails (not existed mail key).
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS => '',
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP => 'not_existed_module',
    ]);
    $this->assertMailReroutedFromTestForm(['to' => static::$originalDestination]);

    // Configure to NOT reroute outgoing emails from our test form.
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP => 'reroute_email_test_email_form',
    ]);
    $this->assertMailNotReroutedFromTestForm(['to' => static::$originalDestination]);
    $this->assertMailHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'MAILKEY-SKIPPED');
  }

}
