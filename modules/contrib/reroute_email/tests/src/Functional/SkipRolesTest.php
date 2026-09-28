<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;

/**
 * Test Reroute Email's with an allow-listed permission.
 *
 * @group reroute_email
 */
class SkipRolesTest extends RerouteEmailBrowserTestBase {

  /**
   * Basic tests for the allowlisted addresses by the permission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testSkipRoles(): void {

    // Create a role.
    $role = $this->drupalCreateRole([]);

    // Configure to skip rerouting by a role.
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => TRUE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => static::$rerouteDestination,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES => [$role],
    ]);

    // Create a user.
    $account = $this->drupalCreateUser();
    $account->save();

    // Submit a test email (should be rerouted).
    $this->assertMailReroutedFromTestForm(['to' => $account->getEmail()]);

    // Add a role to already existed user.
    $account->addRole($role);
    $account->save();

    // Submit a test email (should not be rerouted).
    $this->assertMailNotReroutedFromTestForm(['to' => $account->getEmail()]);
    $this->assertMailHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'ROLE');
  }

}
