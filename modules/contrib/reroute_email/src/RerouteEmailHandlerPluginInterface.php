<?php

namespace Drupal\reroute_email;

/**
 * Defines the interface for RerouteEmailHandler plugins.
 */
interface RerouteEmailHandlerPluginInterface {

  const REROUTE_EMAIL_ENABLE = 'enable';
  const REROUTE_EMAIL_ADDRESS = 'address';
  const REROUTE_EMAIL_ALLOWLIST = 'allowed';
  const REROUTE_EMAIL_ROLES = 'roles';
  const REROUTE_EMAIL_DESCRIPTION = 'description';
  const REROUTE_EMAIL_MESSAGE = 'message';
  const REROUTE_EMAIL_MAILKEYS_WRAPPER = 'mailkeys_wrapper';
  const REROUTE_EMAIL_MAILKEYS = 'mailkeys';
  const REROUTE_EMAIL_MAILKEYS_SKIP = 'mailkeys_skip';

  const HEADER_REASON = "X-Rerouted-Reason";
  const HEADER_STATUS = "X-Rerouted-Status";
  const HEADER_BODY_UPDATED = "X-Rerouted-Body-Details";
  const HEADER_MAIL_ID = "X-Rerouted-Mail-Key";
  const HEADER_FORCE_SKIP = "X-Rerouted-Force";
  const HEADER_BASE_URL = "X-Rerouted-Website";
  const HEADER_ORIGINAL_TO = "X-Rerouted-Original-to";
  const HEADER_ORIGINAL_CC = "X-Rerouted-Original-cc";
  const HEADER_ORIGINAL_BCC = "X-Rerouted-Original-bcc";

  /**
   * Get email ID.
   */
  public function getMailKey(): ?string;

  /**
   * Get email module.
   */
  public function getMailModule(): ?string;

  /**
   * Get email subject.
   */
  public function getSubject(): string;

  /**
   * Get email To address.
   */
  public function getAddressTo();

  /**
   * Get email To address.
   */
  public function setAddressTo(string $to): void;

  /**
   * Get email CC address(es).
   */
  public function getAddressCc();

  /**
   * Set email CC address(es).
   */
  public function setAddressCc(?string $cc): void;

  /**
   * Get email BCC address(es).
   */
  public function getAddressBcc();

  /**
   * Set email BCC address(es).
   */
  public function setAddressBcc(?string $bcc): void;

  /**
   * Get email header.
   */
  public function getHeader(string $name): ?string;

  /**
   * Set email header.
   */
  public function setHeader(string $name, string $value): void;

  /**
   * Check email header availability.
   */
  public function headerExist(string $name): bool;

  /**
   * Get email body.
   *
   * @param bool $force_plain
   *   Whether to force body as plain text.
   */
  public function getBody(bool $force_plain = FALSE);

  /**
   * Prepends a text value to the email body.
   *
   * @param string $prepend
   *   A plain text to be prepended to the email body.
   *   Line break tags will be added automatically in the case of HTML emails.
   */
  public function prependBody(string $prepend): void;

  /**
   * Abort sending of the email.
   */
  public function setAsAborted(bool $skip = TRUE): void;

  /**
   * Get rerouted email raw data for logging purposes.
   */
  public function getRawData(): array;

}
