<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;

/**
 * Test Reroute Email functionality for case sensitivity.
 *
 * @group reroute_email
 */
class CaseSensitivityTest extends RerouteEmailBrowserTestBase {

  /**
   * Test case-sensitive email addresses.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCaseSensitiveAllowedListEmail(): void {

    // Configure reroute_email module.
    $email_allowed_form = "EmAiL@AlLoWed.CoM";
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => TRUE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => static::$rerouteDestination,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST => $email_allowed_form,
    ]);

    // Make sure configured email was set properly.
    $email_allowed_saved = "email@allowed.com";
    $this->assertEquals($this->rerouteConfig->get(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST), $email_allowed_saved, 'Reroute email addresses was properly set.');

    // Submits a test email.
    $email_reverse_case = "eMaIl@aLlOwEd.cOm";
    $this->assertMailNotReroutedFromTestForm(['to' => $email_reverse_case]);
  }

}
