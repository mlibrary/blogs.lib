<?php

namespace Drupal\reroute_email_symfony_mailer\Plugin\RerouteEmailHandler;

use Drupal\reroute_email\RerouteEmailHandlerPluginBase;
use Drupal\reroute_email\RerouteEmailHelpers;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Exception\SkipMailException;

/**
 * Defines the RerouteEmailHandler plug-in for RerouteEmailAdjuster mails.
 *
 * @RerouteEmailHandler(
 *   id = "reroute_email_symfony_mailer",
 *   applied = {"symfony_mailer_adjuster"},
 * )
 */
class SymfonyMailer extends RerouteEmailHandlerPluginBase {

  use RerouteEmailHelpers;

  /**
   * Return a type hint-ed email object.
   *
   * @return ?EmailInterface
   *   An email passed into the plugin.
   */
  public function getReroutedEmail(): ?EmailInterface {
    return $this->configuration['email'];
  }

  /**
   * {@inheritdoc}
   */
  public function getMailKey(): ?string {
    return $this->getReroutedEmail()->getType() . '_' . $this->getReroutedEmail()->getSubType();
  }

  /**
   * {@inheritdoc}
   */
  public function getMailModule(): ?string {
    return $this->getReroutedEmail()->getType();
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject(): string {
    return (string) $this->getReroutedEmail()->getSubject();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressTo() {
    return $this->getAddressesAsString($this->getReroutedEmail()->getTo());
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressTo(string $to): void {
    $this->getReroutedEmail()->setTo($this->extractEmailAddresses($to, FALSE));
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressCc() {
    return $this->getAddressesAsString($this->getReroutedEmail()->getCc());
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressCc(?string $cc): void {
    $this->getReroutedEmail()->setCc($this->extractEmailAddresses($cc, FALSE));
  }

  /**
   * {@inheritdoc}
   */
  public function getAddressBcc() {
    return $this->getAddressesAsString($this->getReroutedEmail()->getBcc());
  }

  /**
   * {@inheritdoc}
   */
  public function setAddressBcc(?string $bcc): void {
    $this->getReroutedEmail()->setBcc($this->extractEmailAddresses($bcc, FALSE));
  }

  /**
   * {@inheritdoc}
   */
  public function getHeader(string $name): ?string {
    if ($this->getReroutedEmail()->getHeaders()->has($name)) {
      return $this->getReroutedEmail()->getHeaders()->get($name)->getBodyAsString();
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeader(string $name, string $value): void {
    $this->getReroutedEmail()->getHeaders()->addTextHeader($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function headerExist(string $name): bool {
    return $this->getReroutedEmail()->getHeaders()->has($name);
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(bool $force_plain = TRUE): ?string {
    if ($force_plain === FALSE && !empty($this->getReroutedEmail()->getHtmlBody())) {
      return $this->getReroutedEmail()->getHtmlBody();
    }
    return $this->getReroutedEmail()->getTextBody();
  }

  /**
   * {@inheritdoc}
   */
  public function prependBody(string $prepend): void {

    // The text is prepended to the plain version only if it exists.
    if ($this->getReroutedEmail()->getTextBody() !== NULL) {
      $this->getReroutedEmail()->setTextBody($prepend . $this->getReroutedEmail()->getTextBody());
    }

    // The HTML version should be always updated.
    $pattern = '/<body.*>/';
    $html_orig = $this->getReroutedEmail()->getHtmlBody();
    if (preg_match($pattern, $html_orig)) {
      $html_prepend = nl2br($prepend);
      $html_new = preg_replace($pattern, "$0$html_prepend", $html_orig, 1);
      $this->getReroutedEmail()->setHtmlBody($html_new);
    }

    // A body tag is not found in the HTML version.
    else {
      $this->getReroutedEmail()->setHtmlBody($prepend . $html_orig);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\symfony_mailer\Exception\SkipMailException
   */
  public function setAsAborted(bool $skip = TRUE): void {
    throw new SkipMailException('Abort sending of the email if the no rerouting addresses provided.');
  }

  /**
   * {@inheritdoc}
   */
  public function getRawData(): array {
    return $this->getReroutedEmail()->__serialize();
  }

  /**
   * Get string emails.
   *
   * @param array $email
   *   An array of email objects.
   *
   * @return string
   *   A string of emails.
   */
  protected function getAddressesAsString(array $email): string {
    if (empty($email)) {
      return "";
    }

    $addresses = [];
    foreach ($email as $address) {
      /** @var \Drupal\symfony_mailer\AddressInterface $address  */
      if (!empty($address->getDisplayName())) {
        $addresses[] = "{$address->getDisplayName()} <{$address->getEmail()}";
      }
      else {
        $addresses[] = $address->getEmail();
      }
    }
    return implode(', ', $addresses);
  }

}
