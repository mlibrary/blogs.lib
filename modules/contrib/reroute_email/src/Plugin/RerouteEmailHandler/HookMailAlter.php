<?php

namespace Drupal\reroute_email\Plugin\RerouteEmailHandler;

use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\reroute_email\RerouteEmailHandlerPluginBase;

/**
 * Defines the RerouteEmailHandler plug-in for hook_mail_alter mails.
 *
 * @RerouteEmailHandler(
 *   id = "reroute_email_hook_mail_alter",
 *   applied = {"hook_mail_alter"},
 * )
 */
class HookMailAlter extends RerouteEmailHandlerPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getMailKey(): ?string {
    return $this->configuration["email"]["id"];
  }

  /**
   * {@inheritdoc}
   */
  public function getMailModule(): ?string {
    return $this->configuration["email"]["module"];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject(): string {
    return $this->configuration["email"]["subject"];
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressTo() {
    if (array_key_exists("to", $this->configuration["email"])) {
      return $this->configuration["email"]["to"];
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressTo(string $to): void {
    $this->configuration["email"]["to"] = $to;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressCc() {
    return $this->getHeaderAddress("cc");
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressCc(?string $cc): void {
    $this->setHeaderAddress($cc, "cc");
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressBcc() {
    return $this->getHeaderAddress("bcc");
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressBcc(?string $bcc): void {
    $this->setHeaderAddress($bcc, "bcc");
  }

  /**
   * A helper function to retrieve cc/bcc addresses.
   */
  private function getHeaderAddress($type) {
    foreach ($this->configuration["email"]['headers'] as $header_name => $value) {
      if (mb_strtolower($type) === mb_strtolower($header_name)) {
        if (!empty($this->configuration["email"]["headers"][$header_name])) {
          return $this->configuration["email"]["headers"][$header_name];
        }
      }
    }
    return NULL;
  }

  /**
   * A helper function to set cc/bcc addresses.
   */
  private function setHeaderAddress($address, $type): void {

    // Remove all available cc headers regardless of their case.
    foreach ($this->configuration["email"]['headers'] as $header_name => $value) {
      if (mb_strtolower($type) === mb_strtolower($header_name)) {
        unset($this->configuration["email"]["headers"][$header_name]);
      }
    }

    // Set the new value.
    if ($address) {
      $this->configuration["email"]["headers"][$type] = $address;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader(string $name): ?string {
    if (empty($this->configuration["email"]["headers"])) {
      return NULL;
    }
    if (empty($this->configuration["email"]["headers"][$name])) {
      return NULL;
    }
    return $this->configuration["email"]["headers"][$name];
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader(string $name, string $value): void {
    $this->configuration["email"]["headers"][$name] = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function headerExist(string $name): bool {
    if (empty($this->configuration["email"]["headers"])) {
      return FALSE;
    }
    return array_key_exists($name, $this->configuration["email"]["headers"]);
  }

  /**
   * {@inheritdoc}
   */
  public function setAsAborted(bool $skip = TRUE): void {
    $this->configuration["email"]['send'] = !$skip;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(bool $force_plain = FALSE) {
    if ($force_plain) {
      // @todo It breaks "Display name <some@email.com>" into "Display name ".
      return MailFormatHelper::htmlToText($this->configuration['email']['body']);
    }
    return $this->configuration["email"]["body"];
  }

  /**
   * {@inheritdoc}
   */
  public function prependBody(string $prepend): void {

    // Prepend explanation message to the body of the email. This must be
    // handled differently depending on whether the body came in as a
    // string or an array. If it came in as a string (despite the fact it
    // should be an array) we'll respect that and leave it as a string.
    if (is_string($this->configuration["email"]['body'])) {
      $this->configuration["email"]['body'] = $prepend . $this->configuration["email"]['body'];
    }
    else {
      array_unshift($this->configuration["email"]['body'], $prepend);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRawData(): array {
    $message_copy = $this->configuration["email"];

    // Extensive params keys cause OOM error when outputting the value.
    // But we need to keep 'params' (e.g. attachments).
    unset($message_copy['params']);

    // Simplify subject to avoid OOM error in the variable output.
    if ($message_copy['subject'] instanceof TranslatableMarkup) {
      $message_copy['subject'] = $message_copy['subject']->render();
    }

    return $message_copy;
  }

}
