<?php

namespace Drupal\reroute_email;

use Drupal\Core\Plugin\PluginBase;

/**
 * Defines the base class for EmailBuilder plug-ins.
 */
abstract class RerouteEmailHandlerPluginBase extends PluginBase implements RerouteEmailHandlerPluginInterface {

  use RerouteEmailHelpers;

  /**
   * A main function to process with rerouting.
   */
  public function process(): void {
    // Skip already processed emails.
    if ($this->headerExist(RerouteEmailHandlerPluginInterface::HEADER_STATUS)) {
      return;
    }

    // Allow other modules to decide whether the email should be force rerouted
    // by specify a special header 'X-Rerouted-Force' to TRUE or FALSE. Any
    // module can add this header to any own or other modules emails.
    if ($this->headerExist(RerouteEmailHandlerPluginInterface::HEADER_FORCE_SKIP) &&
      FALSE === (bool) $this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_FORCE_SKIP)) {

      // We ignore all reroute settings if X-Rerouted-Force header is TRUE.
      return;
    }

    // There is no value for X-Rerouted-Force header in the message. Let's
    // determine if the message should be rerouted according to the module
    // settings values.
    elseif (FALSE === $this->rerouteIsNeeded()) {
      return;
    }

    global $base_url;
    $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_BASE_URL, $base_url);
    $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_MAIL_ID, $this->getMailKey());
    $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_TO, $this->getAddressTo());

    // Get reroute_email_address, or use system.site.mail if not set.
    $rerouting_addresses = $this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS] ?? \Drupal::config('system.site')->get('mail');
    $this->setAddressTo($rerouting_addresses);

    // Proceed Cc address.
    if ($original_cc = $this->getAddressCc()) {
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_CC, $original_cc);
      $this->setAddressCc(NULL);
    }

    // Proceed Bcc address.
    if ($original_bcc = $this->getAddressBcc()) {
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_BCC, $original_bcc);
      $this->setAddressBcc(NULL);
    }

    // Abort sending of the email if the no rerouting addresses provided.
    if ($rerouting_addresses === '') {
      $this->setAsAborted();
    }

    // If configured, display a message to users to let them know.
    if ($this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE]) {
      \Drupal::messenger()
        ->addMessage($this->t('An email (ID: %message_id) either aborted or rerouted to the configured address. Site administrators can check the recent log entries for complete details on the rerouted email. For more details please refer to Reroute Email settings.', ['%message_id' => $this->getMailKey()]));
    }

    // Record a variable dump of the email in the recent log entries.
    \Drupal::logger('reroute_email')
      ->notice('An email (ID: %message_id) was either rerouted or aborted.<br/>Detailed email data: Array $message <pre>@message</pre>', [
        '%message_id' => $this->getMailKey(),
        '@message' => json_encode($this->getRawData(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT),
      ]);
  }

  /**
   * A helper method to add reroute details to the email (if needed).
   */
  public function processBody(): void {
    // Do we need to add reroute data to the email according to the settings?
    if (!($this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION] ?? FALSE)) {
      return;
    }

    // Do not update the email body for non-rerouted emails.
    if ($this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS) !== "REROUTED") {
      return;
    }

    // Do not update the email body twice.
    if ($this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_BODY_UPDATED) === "UPDATED") {
      return;
    }

    $original_to = $this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_TO);
    $message_lines = [
      $this->t('This email was rerouted.'),
      $this->t('Originally to: @to', ['@to' => empty($original_to) ? $this->t('[to] is missing') : $original_to]),
    ];

    // Add basic email values to the message if they are set.
    if ($this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_BASE_URL)) {
      $message_lines[] = $this->t('Web site: @site', ['@site' => $this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_BASE_URL)]);
    }
    if ($this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_MAIL_ID)) {
      $message_lines[] = $this->t('Mail key: @key', ['@key' => $this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_MAIL_ID)]);
    }
    if ($this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_CC)) {
      $message_lines[] = $this->t('Originally cc: @cc', ['@cc' => $this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_CC)]);
    }
    if ($this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_BCC)) {
      $message_lines[] = $this->t('Originally bcc: @bcc', ['@bcc' => $this->getHeader(RerouteEmailHandlerPluginInterface::HEADER_ORIGINAL_BCC)]);
    }

    // Simple separator between reroute and original messages.
    $message_lines[] = '-----------------------';
    $message_lines[] = '';

    $this->prependBody(implode(PHP_EOL, $message_lines));
    $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_BODY_UPDATED, "UPDATED");
  }

  /**
   * A helper method to determine a need to reroute.
   *
   * @return bool
   *   Return TRUE if email should be rerouted, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function rerouteIsNeeded(): bool {
    // Disable rerouting according to admin settings.
    $config = \Drupal::config('reroute_email.settings');
    if (empty($config->get(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE))) {
      return FALSE;
    }

    // Check configured mail keys filters.
    $keys = reroute_email_split_string($this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS]);
    if (!empty($keys) && !(in_array($this->getMailKey(), $keys, TRUE) || in_array($this->getMailModule(), $keys, TRUE))) {
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'MAILKEY-ALLOWED');
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'NOT-REROUTED');
      return FALSE;
    }

    // Check configured mail keys to skip.
    $keys_skip = reroute_email_split_string($this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP]);
    if (!empty($keys_skip) && (in_array($this->getMailKey(), $keys_skip, TRUE) || in_array($this->getMailModule(), $keys_skip, TRUE))) {
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'MAILKEY-SKIPPED');
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'NOT-REROUTED');
      return FALSE;
    }

    // Split addresses into arrays.
    $original_addresses = $this->extractEmailAddresses($this->getAddressTo());
    $original_addresses = array_unique(array_merge($original_addresses, $this->extractEmailAddresses($this->getAddressCc())));
    $original_addresses = array_unique(array_merge($original_addresses, $this->extractEmailAddresses($this->getAddressBcc())));

    $allowlisted_addresses = reroute_email_split_string($this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST]);
    $allowlisted_patterns = [];

    // Split allowed domains and partial addresses patterns from the allowlist.
    foreach ($allowlisted_addresses as $key => $email) {
      if (substr_count($email, '*') > 0) {
        $email = '/^' . preg_quote($email, '/') . '$/';
        $allowlisted_patterns[$email] = str_replace('\*', '[^@]+', $email);
        unset($allowlisted_addresses[$key]);
      }
    }

    // Compare original addresses with the allow list.
    $invalid = 0;
    foreach ($original_addresses as $email) {

      // Just ignore all invalid email addresses.
      if (\Drupal::service('email.validator')->isValid($email) === FALSE) {
        $invalid++;
        continue;
      }

      // Check email in the allowlist.
      if (in_array($email, $allowlisted_addresses, TRUE)) {
        $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'ALLOWLISTED');
        continue;
      }

      // Check allowed domains and partial addresses patterns from allowlist.
      foreach ($allowlisted_patterns as $pattern) {
        if (preg_match($pattern, $email)) {
          $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'PATTERN');
          continue 2;
        }
      }

      // Check users by roles.
      if ($this->emailHasAllowlistedRole($email)) {
        $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'ROLE');
        continue;
      }

      // No need to continue if at least one address should be rerouted.
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'REROUTED');
      return TRUE;
    }

    // Reroute if all addresses are invalid.
    if (count($original_addresses) === $invalid) {
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_REASON, 'INVALID-ADDRESSES');
      $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'REROUTED');
      return TRUE;
    }

    // All email addresses are in the allowed list.
    $this->setHeader(RerouteEmailHandlerPluginInterface::HEADER_STATUS, 'NOT-REROUTED');
    return FALSE;
  }

  /**
   * Check email association to a user with the skipped role.
   *
   * @param string $email
   *   Email to be checked.
   *
   * @return bool
   *   TRUE for emails that should be allowlisted and not rerouted.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function emailHasAllowlistedRole(string $email): bool {
    $accounts = \Drupal::entityTypeManager()
      ->getStorage('user')
      ->loadByProperties(['mail' => $email]);
    if (empty($accounts)) {
      return FALSE;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = reset($accounts);

    $allowlisted_roles = (array) ($this->configuration["settings"][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES] ?? []);
    foreach ($allowlisted_roles as $allowlisted_role) {
      if ($account->hasRole($allowlisted_role)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
