<?php

namespace Drupal\openid_connect;

/**
 * Creates and validates state tokens.
 *
 * @package Drupal\openid_connect
 */
interface OpenIDConnectStateTokenInterface {

  /**
   * Creates a state token and stores it in the session for later validation.
   *
   * @return string
   *   A state token that later can be validated to prevent request forgery.
   */
  public function generateToken(): string;

  /**
   * Confirms anti-forgery state token.
   *
   * @param string $state_token
   *   The state token that is used for validation.
   *
   * @return bool
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public function confirm(string $state_token): bool;

}
