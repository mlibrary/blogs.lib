<?php

namespace Drupal\openid_connect;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Creates and validates state tokens.
 *
 * @package Drupal\openid_connect
 */
interface OpenIDConnectSessionInterface extends ContainerInjectionInterface {

  /**
   * Get the destination redirect path and langcode from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return array
   *   The destination path and langcode.
   */
  public function retrieveDestination(bool $clear = TRUE): array;

  /**
   * Save the current path and langcode, for redirecting after authorization.
   *
   * @see \Drupal\openid_connect\Controller\OpenIDConnectRedirectController::authenticate()
   */
  public function saveDestination();

  /**
   * Save a target_link_uri as the redirect destination in the session.
   *
   * This will convert the user provided string to a \Drupal\Core\Url object
   * and will ensure it is not an external link before saving the destination
   * parameter. The string passed _must_ begin with a '/'.
   *
   * @param string $target_link_uri
   *   The internal url that the user should be redirected after login.
   *   The string must begin with a '/'.
   */
  public function saveTargetLinkUri(string $target_link_uri): void;

  /**
   * Get the operation details from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return array
   *   The operation details.
   */
  public function retrieveOp(bool $clear = TRUE): array;

  /**
   * Save the operation details in the session.
   *
   * @param string $op
   *   The operation.
   * @param int|null $uid
   *   The user ID.
   */
  public function saveOp(string $op, ?int $uid = NULL);

  /**
   * Get the id token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, if this is set to TRUE.
   *
   * @return string|null
   *   The id token.
   */
  public function retrieveIdToken(bool $clear = FALSE): ?string;

  /**
   * Save the id token in the session.
   *
   * @param string $token
   *   The id token.
   */
  public function saveIdToken(string $token);

  /**
   * Get the access token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, if this is set to TRUE.
   *
   * @return string|null
   *   The access token.
   */
  public function retrieveAccessToken(bool $clear = FALSE): ?string;

  /**
   * Save the access token in the session.
   *
   * @param string $token
   *   The access token.
   */
  public function saveAccessToken(string $token);

  /**
   * Get the refresh token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, if this is set to TRUE.
   *
   * @return string|null
   *   The refresh token.
   */
  public function retrieveRefreshToken(bool $clear = FALSE): ?string;

  /**
   * Save the refresh token in the session.
   *
   * @param string $token
   *   The refresh token.
   */
  public function saveRefreshToken(string $token);

  /**
   * Get the token expire timestamp from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, if this is set to TRUE.
   *
   * @return int|null
   *   The token expire timestamp.
   */
  public function retrieveExpireToken(bool $clear = FALSE): ?int;

  /**
   * Save the token expire timestamp in the session.
   *
   * @param int $timestamp
   *   The token expire timestamp.
   */
  public function saveExpireToken(int $timestamp);

  /**
   * Get the state token from the session.
   *
   * @param bool $clear
   *   The value is cleared from the session, unless this is set to FALSE.
   *
   * @return string|null
   *   The state token.
   */
  public function retrieveStateToken(bool $clear = TRUE): ?string;

  /**
   * Save the state token in the session.
   *
   * @param string $token
   *   The state token.
   */
  public function saveStateToken(string $token);

}
