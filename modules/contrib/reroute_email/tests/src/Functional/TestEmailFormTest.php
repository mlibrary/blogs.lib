<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\TestTools\Random;
use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;

/**
 * Test Reroute Email's form for sending a test email.
 *
 * @group reroute_email
 */
class TestEmailFormTest extends RerouteEmailBrowserTestBase {

  /**
   * Basic tests for reroute_email Test Email form.
   *
   * Check if submitted form values are properly submitted and rerouted.
   * Test Subject, To, Cc, Bcc and Body submitted values, form validation,
   * default values, and submission with invalid email addresses.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @dataProvider formValuesProvider
   */
  public function testFormTestEmail($enabled, $allowlisted, $post, $rerouted): void {

    // Configure to reroute all outgoing emails.
    $this->configureRerouteEmail([
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => $enabled,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => static::$rerouteDestination,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST => $allowlisted,
    ]);

    // Check Subject field default value.
    $this->drupalGet($this->rerouteTestFormPath);
    $this->assertSession()->fieldValueEquals('subject', $this->rerouteFormDefaultSubject);
    $this->assertSession()->fieldValueEquals('body', $this->rerouteFormDefaultBody);

    $this->assertMailReroutedFromTestForm($post, $rerouted);
  }

  /**
   * Data provider for ::testFormTestEmail().
   */
  public static function formValuesProvider(): array {
    // All fields are set correctly.
    $data['correct_fields'] = [
      'enabled' => TRUE,
      'allowlisted' => '',
      'post' => [
        'to' => static::$originalDestination,
        'cc' => Random::machineName() . '@not-allowed.com',
        'bcc' => Random::machineName() . '@not-allowed.com',
        'subject' => 'Test Reroute Email Test Email Form',
        'body' => 'Testing email rerouting and the Test Email form',
      ],
      'rerouted' => TRUE,
    ];

    // Test a form with empty values for non-required fields.
    $data['empty_fields_1'] = [
      'enabled' => TRUE,
      'allowlisted' => '',
      'post' => [
        'to' => '',
        'cc' => '',
        'bcc' => '',
        'subject' => '',
        'body' => '',
      ],
      'rerouted' => TRUE,
    ];
    $data['empty_fields_2'] = [
      'enabled' => TRUE,
      'allowlisted' => static::$originalDestination . ", ",
      'post' => [
        'to' => static::$originalDestination,
        'cc' => '',
        'bcc' => '',
        'subject' => '',
        'body' => '',
      ],
      'rerouted' => FALSE,
    ];

    // Tests for partial emails amd domain wildcards in the allowed list.
    $data['partial_emails_1'] = [
      'enabled' => TRUE,
      'allowlisted' => 'some+*@allowlisted.com',
      'post' => ['to' => 'email@allowlisted.com'],
      'rerouted' => TRUE,
    ];
    $data['partial_emails_2'] = [
      'enabled' => TRUE,
      'allowlisted' => 'some+*@allowlisted.com',
      'post' => ['to' => 'some+partial@allowlisted.com'],
      'rerouted' => FALSE,
    ];
    $data['partial_emails_3'] = [
      'enabled' => TRUE,
      'allowlisted' => 'some+*@allowlisted.com, *@great-company.com',
      'post' => ['to' => 'some+partial@allowlisted.com, email@great-company.com'],
      'rerouted' => FALSE,
    ];

    $email_allowlisted_one = Random::machineName() . '@allowlisted.com';
    $email_allowlisted_two = Random::machineName() . '@allowlisted.com';
    $email_allowlisted_three = Random::machineName() . '@allowlisted.com';
    $email_allowlisted_not = Random::machineName() . '@not-allowlisted.com';

    // Check rerouting by `cc` and `bcc` with allowlisted `to` value.
    $data['cc_bcc_1'] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'to' => $email_allowlisted_one,
        'cc' => $email_allowlisted_two,
        'bcc' => $email_allowlisted_three,
      ],
      'rerouted' => FALSE,
    ];
    $data['cc_bcc_2'] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'to' => $email_allowlisted_one,
        'cc' => $email_allowlisted_not,
      ],
      'rerouted' => TRUE,
    ];
    $data['cc_bcc_3'] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'cc' => $email_allowlisted_one,
        'bcc' => $email_allowlisted_not,
      ],
      'rerouted' => TRUE,
    ];
    $data['cc_bcc_4'] = [
      'enabled' => TRUE,
      'allowlisted' => '*@allowlisted.com',
      'post' => [
        'to' => '',
        'cc' => $email_allowlisted_not,
        'bcc' => $email_allowlisted_not,
      ],
      'rerouted' => TRUE,
    ];

    return $data;
  }

}
