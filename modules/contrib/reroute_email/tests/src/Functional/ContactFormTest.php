<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\TestTools\Random;
use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;

/**
 * Test ability to reroute mail sent from the Contact module form.
 *
 * @group reroute_email
 */
class ContactFormTest extends RerouteEmailBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact'];

  /**
   * Contact form confirmation message text.
   *
   * @var string
   */
  protected string $confirmationMessage = 'Your message has been sent.';

  /**
   * Contact form configuration path.
   *
   * @var string
   */
  protected string $contactFormId = 'feedback';

  /**
   * Enables modules and create user with specific permissions.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setUp(): void {
    // Adds more permissions to be able to manipulate the contact forms.
    $this->permissions[] = 'administer contact forms';
    $this->permissions[] = 'access site-wide contact form';
    parent::setUp();

    // Creates a "feedback" contact form.
    $this->drupalGet('admin/structure/contact/add');
    $this->submitForm([
      'label' => $this->contactFormId,
      'id' => $this->contactFormId,
      'recipients' => static::$originalDestination,
      'message' => $this->confirmationMessage,
      'selected' => TRUE,
    ], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Makes sure that the flood controls don't break the test.
    \Drupal::service('config.factory')->getEditable('contact.settings')
      ->set('flood.limit', 1000)
      ->set('flood.interval', 60);
  }

  /**
   * Data provider for ::testBasicNotification().
   */
  public static function basicNotificationDataProvider(): array {
    $data['base_test'] = [
      'reroute_configure' => [
        RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => TRUE,
        RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => static::$rerouteDestination,
      ],
      'notification_recipient' => static::$originalDestination,
      'reroute_expected' => TRUE,
    ];

    $data['additional_email'] = [
      'reroute_configure' => [
        RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => TRUE,
        RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST => static::$rerouteDestination . ", *@allowed-domain.com",
      ],
      'notification_recipient' => Random::getGenerator()->word(10) . '@allowed-domain.com',
      'reroute_expected' => FALSE,
    ];

    $data['reroute_disabled'] = [
      'reroute_configure' => [
        RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => FALSE,
      ],
      'notification_recipient' => static::$originalDestination,
      'reroute_expected' => FALSE,
    ];

    return $data;
  }

  /**
   * Asserts the latest email from the contact form.
   *
   * @param array $reroute_configure
   *   An array of reroute configuration.
   * @param string $notification_recipient
   *   An email address for the notifications.
   * @param bool $reroute_expected
   *   Expected reroute status.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   *
   * @dataProvider basicNotificationDataProvider
   */
  public function testBasicNotification(
    array $reroute_configure,
    string $notification_recipient,
    bool $reroute_expected = TRUE,
  ): void {
    // Configures reroute module settings.
    $this->configureRerouteEmail($reroute_configure);

    // Configures contact form settings.
    $this->drupalGet('admin/structure/contact/manage/' . $this->contactFormId);
    $this->submitForm(['recipients' => $notification_recipient], $this->t('Save'));

    // Prepares an array of the expected email.
    $email_details = [
      'to' => $notification_recipient,
      'subject' => $this->getRandomGenerator()->sentences(6, TRUE),
      'body' => $this->getRandomGenerator()->sentences(6, TRUE),
    ];

    // Goes to the contact page and sends an email.
    $this->drupalGet('contact');
    $this->submitForm([
      'subject[0][value]' => $email_details['subject'],
      'message[0][value]' => $email_details['body'],
    ], 'Send message');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->confirmationMessage);

    // Verifies the email.
    $email_details['subject'] = "[{$this->contactFormId}] {$email_details['subject']}";
    $this->assertMailRerouted($email_details, $reroute_expected);
  }

}
