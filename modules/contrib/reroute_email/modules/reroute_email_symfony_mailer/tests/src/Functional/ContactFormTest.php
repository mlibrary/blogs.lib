<?php

namespace Drupal\Tests\reroute_email_symfony_mailer\Functional;

use Drupal\Tests\reroute_email\Functional\ContactFormTest as MainContactTest;

/**
 * Test ability to reroute mail sent from the Contact module form.
 *
 * @group reroute_email_symfony_mailer
 */
class ContactFormTest extends MainContactTest {

  /**
   * Reroute plugin id.
   */
  protected string $reroutePluginId = 'reroute_email_symfony_mailer';

  /**
   * Mail collector state.
   */
  protected string $mailCollectorState = 'mailer_test.emails';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'reroute_email_symfony_mailer',

    // Set email transport DSN to 'null://default'.
    'symfony_mailer_test',
  ];

}
