<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\TestTools\Random;

/**
 * Test Reroute Email's form for sending a test email using invalid data.
 *
 * @group reroute_email
 */
class DisplayNameTest extends TestEmailFormTest {

  /**
   * Data provider for ::testFormTestEmail().
   */
  public static function formValuesProvider(): array {
    // Check if recipient fields support an email with additional display name.
    // like "Display Name <display.name@example.com>".
    $email_allowlisted_one = Random::machineName() . '@allowlisted.com';
    $email_allowlisted_two = Random::machineName() . '@allowlisted.com';
    $email_allowlisted_three = Random::machineName() . '@allowlisted.com';
    $email_allowlisted_not = Random::machineName() . '@not-allowlisted.com';
    $data['display_names_1'] = [
      'enabled' => TRUE,
      'allowlisted' => "{$email_allowlisted_one}, {$email_allowlisted_two}",
      'post' => [
        'to' => "Some Display Name <{$email_allowlisted_not}>",
      ],
      'rerouted' => TRUE,
    ];
    $data['display_names_2'] = [
      'enabled' => TRUE,
      'allowlisted' => "{$email_allowlisted_one}, {$email_allowlisted_two}, {$email_allowlisted_three}",
      'post' => [
        'to' => "Display Name <{$email_allowlisted_one}>",
        'cc' => "Display Name &*% (Test Special Chars) <{$email_allowlisted_two}>",
        'bcc' => "Display Name @ <{$email_allowlisted_three}>",
      ],
      'rerouted' => FALSE,
    ];

    return $data;
  }

}
