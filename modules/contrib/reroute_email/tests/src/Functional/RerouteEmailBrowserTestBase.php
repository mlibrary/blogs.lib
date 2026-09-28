<?php

namespace Drupal\Tests\reroute_email\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\Config;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;
use Drupal\user\Entity\User;

/**
 * Base test class for Reroute Email test cases.
 */
abstract class RerouteEmailBrowserTestBase extends BrowserTestBase {

  use AssertMailTrait;
  use StringTranslationTrait;

  /**
   * Reroute email plugin id.
   *
   * @var string
   */
  protected string $reroutePluginId = "reroute_email_hook_mail_alter";

  /**
   * A mail collector's state id.
   *
   * @var string
   */
  protected string $mailCollectorState = "system.test_mail_collector";

  /**
   * An editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $rerouteConfig;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['reroute_email'];

  /**
   * Permissions required by the user to perform the tests.
   *
   * @var array
   */
  protected array $permissions = [
    'administer reroute email',
  ];

  /**
   * User object to perform site browsing.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $adminUser;

  /**
   * Original email address used for the tests.
   *
   * @var string
   */
  protected static string $originalDestination = 'email@original-destination.com';

  /**
   * Reroute email destination address used for the tests.
   *
   * @var string
   */
  protected static string $rerouteDestination = 'email@reroute-destination.com';

  /**
   * Path of the module's settings form.
   *
   * @var string
   */
  protected string $rerouteSettingsFormPath = 'admin/config/development/reroute_email';

  /**
   * Path for reroute email test form.
   *
   * @var string
   */
  protected string $rerouteTestFormPath = 'admin/config/development/reroute_email/test';

  /**
   * Default subject value in the form.
   *
   * @var string
   */
  protected string $rerouteFormDefaultSubject = 'Reroute Email Test';

  /**
   * Default subject value in the form.
   *
   * @var string
   */
  protected string $rerouteFormDefaultBody = 'Reroute Email Body';

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function setUp(): void {
    parent::setUp();
    $this->rerouteConfig = $this->config('reroute_email.settings');

    // Authenticate test user.
    $this->adminUser = $this->drupalCreateUser($this->permissions);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Get reroute email plugin for the recently sent email.
   */
  public function getRecentEmail(): RerouteEmailHandlerPluginInterface {
    $emails = $this->container->get('state')->get($this->mailCollectorState, []);
    $email = end($emails);

    /** @var \Drupal\reroute_email\RerouteEmailHandlerPluginManager $reroute_handlers_manager */
    $reroute_handlers_manager = \Drupal::service('plugin.manager.reroute_email_handler');

    return $reroute_handlers_manager->createInstance($this->reroutePluginId, [
      'settings' => $this->rerouteConfig->getRawData(),
      'email' => $email,
    ]);
  }

  /**
   * Helper function to configure Reroute Email Settings.
   *
   * An array of configuration options to set. All params are optional.
   * RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_* define should be
   * used as for array keys.
   * Default values can be found at reroute_email.schema.yml file.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function configureRerouteEmail($post_values): void {
    $schema_values = [
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE => FALSE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => '',
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST => '',
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES => [],
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION => TRUE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE => TRUE,
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS => '',
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP => '',
    ];

    // Configure to Reroute Email settings form.
    foreach ($schema_values as $setting => $value) {
      $current_values[$setting] = $this->rerouteConfig->get($setting) ?? $value;
      $post_values[$setting] = $post_values[$setting] ?? $current_values[$setting];

      if (is_array($post_values[$setting])) {
        foreach ($post_values[$setting] as $val) {
          $post_values[$setting . "[{$val}]"] = $val;
        }
        unset($post_values[$setting]);
      }
    }

    // Submit Reroute Email Settings form and check if it was successful.
    $this->drupalGet($this->rerouteSettingsFormPath);
    $this->submitForm($post_values, $this->t('Save configuration'));
    $this->assertSession()->pageTextContains($this->t('The configuration options have been saved.'));

    // Rebuild config values after form submit.
    $this->rerouteConfig = $this->config('reroute_email.settings');
  }

  /**
   * Submit test email form and assert not rerouting.
   *
   * @param array $post
   *   An array of post data: 'to', 'cc', 'bcc', 'subject', 'body'.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertMailNotReroutedFromTestForm(array $post): void {
    $this->assertMailReroutedFromTestForm($post, FALSE);
  }

  /**
   * Submit test email form and assert rerouting.
   *
   * @param array $post
   *   An array of post data: 'to', 'cc', 'bcc', 'subject', 'body'.
   * @param bool $reroute_expected
   *   Expected reroute status.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function assertMailReroutedFromTestForm(array $post, bool $reroute_expected = TRUE): void {
    // Clear state before next step, due to initialization of MailerTestService.
    // @see symfony_mailer_test_mailer_init.
    // It is used as a workaround here because symfony mailer state collector
    // cannot store more than 1 email. So, applying this workaround will clear
    // the state before each form submit.
    \Drupal::state()->delete($this->mailCollectorState);

    // Submit the test form.
    $this->drupalGet($this->rerouteTestFormPath);
    $this->submitForm($post, $this->t('Send email'));
    $this->assertSession()->pageTextContains($this->t('Test email submitted for delivery from test form.'));

    $post['subject'] = $post['subject'] ?? $this->rerouteFormDefaultSubject;
    $post['body'] = $post['body'] ?? $this->rerouteFormDefaultBody;
    $this->assertMailRerouted($post, $reroute_expected);
  }

  /**
   * Asserts the latest email rerouting.
   *
   * @param array $params
   *   Details about the email (to, cc, bcc, body, subject).
   * @param bool $reroute_expected
   *   Expected reroute status.
   */
  public function assertMailRerouted(array $params, bool $reroute_expected = TRUE): void {
    // Destination address can contain display name with symbols "<" and ">".
    // So, we can't use $this->t() or FormattableMarkup here.
    $search_originally_to = sprintf('Originally to: %s', empty($params['to']) ? '[to] is missing' : $params['to']);

    // Check email properties related to `to` value.
    if ($reroute_expected) {
      $this->assertMailTo($this->rerouteConfig->get(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS), new FormattableMarkup('An email was properly rerouted to the email address: @address.', ['@address' => static::$rerouteDestination]));
      $this->assertMailHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_TO, $params['to'] ?? '', new FormattableMarkup('X-Rerouted-Original-to is correctly set to submitted value: @address', ['@address' => $params['to'] ?? '']));
      $this->assertMailBodyContains($search_originally_to, 'Found the correct "Originally to" line in the body.');
    }
    else {
      $this->assertMailTo($params['to'] ?? '', new FormattableMarkup('An email was properly sent to the email address: @address.', ['@address' => $params['to']]));
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_TO);
      $this->assertMailBodyNotContains($search_originally_to);
    }

    // Check email subject.
    if (!empty($params['subject'])) {
      $this->assertMailSubject($params['subject'], new FormattableMarkup('Subject is correctly set to submitted value: @subject', ['@subject' => $params['subject']]));
    }

    // Check email body can be found in the email.
    if (!empty($params['body'])) {
      $this->assertMailBodyContains($params['body'], 'Body contains the value submitted through the form.');
    }

    // Check the Cc and Bcc are the ones submitted through the form and were
    // added to the message body value.
    $this->assertMailReroutedHeaders('cc', $params['cc'] ?? NULL, $reroute_expected);
    $this->assertMailReroutedHeaders('bcc', $params['bcc'] ?? NULL, $reroute_expected);

    // Check reroute_mail module special headers.
    if ($this->rerouteConfig->get(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE) === FALSE) {
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_FORCE_SKIP);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_STATUS);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_REASON);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_TO);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_CC);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_BCC);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_MAIL_ID);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_BASE_URL);
      $this->assertMailHeaderNotExist(RerouteEmailHandlerPluginInterface::HEADER_BODY_UPDATED);
    }
    elseif ($reroute_expected) {
      $this->assertMailHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'REROUTED');
    }
    else {
      $this->assertMailHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'NOT-REROUTED');
      $this->assertMailHeaderExist(RerouteEmailHandlerPluginInterface::HEADER_REASON);
    }
  }

  /**
   * Submit test email form and assert rerouting.
   *
   * @param string $header
   *   A name of the header to check.
   * @param string|null $value
   *   An expected value of the header.
   * @param bool $rerouted
   *   Expected reroute status.
   */
  public function assertMailReroutedHeaders(string $header, ?string $value, bool $rerouted = TRUE): void {
    // Check email properties related to `to` value.
    // Destination address can contain display name with symbols "<" and ">".
    // So, we can't use $this->t() or FormattableMarkup here.
    $header_body_search = sprintf('Originally %s: %s', $header, $value);
    $header_rerouted = 'X-Rerouted-Original-' . $header;

    // Both rerouted and not rerouted mail should not have empty header.
    if (empty($value)) {
      $this->assertMailHeaderNotExist($header);
      $this->assertMailHeaderNotExist($header_rerouted);
      $this->assertMailBodyNotContains($header_body_search);
    }
    elseif ($rerouted === FALSE) {
      $this->assertMailHeader($header, $value);
      $this->assertMailHeaderNotExist($header_rerouted);
      $this->assertMailBodyNotContains($header_body_search);
    }
    elseif ($rerouted === TRUE) {
      $this->assertMailHeaderNotExist($header);
      $this->assertMailHeader($header_rerouted, $value);
      $this->assertMailBodyContains($header_body_search);
    }
  }

  /**
   * Asserts that the most recently sent email message has the header in it.
   *
   * @param string $header
   *   A name of the header to check.
   * @param string|null $value
   *   An expected value of the header.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailHeader(string $header, ?string $value, $message = ''): void {
    if (empty($message)) {
      $message = new FormattableMarkup('Header "@header" is correctly set to submitted value: @value', [
        '@header' => $header,
        '@value' => $value,
      ]);
    }

    $this->assertEquals($this->getRecentEmail()->getHeader($header), $value, $message);
  }

  /**
   * Asserts that the most recently sent email message has the header in it.
   *
   * @param string $header
   *   A name of the header to check.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailHeaderExist(string $header, $message = ''): void {
    if (empty($message)) {
      $message = new FormattableMarkup('Header "@header" exist in the recent email.', [
        '@header' => $header,
      ]);
    }

    $this->assertTrue($this->getRecentEmail()->headerExist($header), $message);
  }

  /**
   * Asserts that the most recently sent email message has not the header in it.
   *
   * @param string $header
   *   A name of the header to check.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailHeaderNotExist(string $header, $message = ''): void {
    if (empty($message)) {
      $message = new FormattableMarkup('Header "@header" correctly does not exist in the recent email.', [
        '@header' => $header,
      ]);
    }

    $this->assertFalse($this->getRecentEmail()->headerExist($header), $message);
  }

  /**
   * Asserts that the most recently sent email message has the given "to" value.
   *
   * @param string $address
   *   The email address.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailTo(string $address, $message = ''): void {
    $this->assertEquals($address, $this->getRecentEmail()->getAddressTo(), $message);
  }

  /**
   * Asserts the most recently sent email subject.
   *
   * @param string $subject
   *   The subject to check.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailSubject(string $subject, $message = ''): void {
    $this->assertEquals($subject, $this->getRecentEmail()->getSubject(), $message);
  }

  /**
   * Asserts that the most recently sent email body contains the string.
   *
   * @param string $search
   *   The string for partially search in the email body.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailBodyContains(string $search, $message = ''): void {
    $this->assertStringContainsString($search, $this->getRecentEmail()->getBody(), $message);
  }

  /**
   * Asserts that the most recently sent email body does not contain the string.
   *
   * @param string $search
   *   The string for partially search in the email body.
   * @param \Drupal\Component\Render\FormattableMarkup|string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   */
  public function assertMailBodyNotContains(string $search, $message = ''): void {
    $this->assertStringNotContainsString($search, $this->getRecentEmail()->getBody(), $message);
  }

}
