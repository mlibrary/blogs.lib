<?php

namespace Drupal\Tests\reroute_email_symfony_mailer\Functional;

use Drupal\Tests\reroute_email\Functional\TestEmailFormTest as MainTestEmailFormTest;
use Drupal\symfony_mailer_test\MailerTestServiceInterface;

/**
 * Test Reroute Email's form for sending a test email.
 *
 * @group reroute_email_symfony_mailer
 */
class TestEmailFormTest extends MainTestEmailFormTest {

  /**
   * {@inheritdoc}
   */
  protected string $reroutePluginId = "reroute_email_symfony_mailer";

  /**
   * {@inheritdoc}
   */
  protected string $mailCollectorState = MailerTestServiceInterface::STATE_KEY;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'reroute_email_symfony_mailer',

    // Set email transport DSN to 'null://default'.
    'symfony_mailer_test',
  ];

}
