<?php

namespace Drupal\Tests\reroute_email\Functional;

/**
 * Test Reroute Email's form for sending a test email using invalid data.
 *
 * @group reroute_email
 */
class InvalidAddressesTest extends TestEmailFormTest {

  /**
   * Data provider for ::testFormTestEmail().
   */
  public static function formValuesProvider(): array {
    // A test with invalid emails and default values for subject and body.
    $data[] = [
      'enabled' => TRUE,
      'allowlisted' => '',
      'post' => [
        'to' => 'To address invalid format',
        'cc' => 'Cc address invalid format',
        'bcc' => 'Bcc address invalid format',
      ],
      'rerouted' => TRUE,
    ];
    $data[] = [
      'enabled' => FALSE,
      'allowlisted' => '',
      'post' => [
        'to' => 'To address invalid format',
        'cc' => 'Cc address invalid format',
        'bcc' => 'Bcc address invalid format',
      ],
      'rerouted' => FALSE,
    ];

    return $data;
  }

}
