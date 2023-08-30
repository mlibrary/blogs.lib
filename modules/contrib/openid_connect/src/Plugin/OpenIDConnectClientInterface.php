<?php

namespace Drupal\openid_connect\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defines an interface for OpenID Connect client plugins.
 */
interface OpenIDConnectClientInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginWithFormsInterface {

  /**
   * Returns an array of endpoints.
   *
   * @return array
   *   An array with the following keys:
   *   - authorization: The full url to the authorization endpoint.
   *   - token: The full url to the token endpoint.
   *   - userinfo: The full url to the userinfo endpoint.
   */
  public function getEndpoints(): array;

  /**
   * Gets an array of of scopes.
   *
   * This method allows a client to override the default minimum set of scopes
   * assumed by OpenIDConnectClaims::getScopes();
   *
   * @return string[]|null
   *   A space separated list of scopes.
   */
  public function getClientScopes(): ?array;

  /**
   * Redirects the user to the authorization endpoint.
   *
   * The authorization endpoint authenticates the user and returns them
   * to the redirect_uri specified previously with an authorization code
   * that can be exchanged for an access token.
   *
   * @param string $scope
   *   Name of scope(s) that with user consent will provide access to otherwise
   *   restricted user data. Defaults to "openid email".
   * @param array $additional_params
   *   Allow additional query parameters to be passed to the authorization url.
   *   See: https://openid.net/specs/openid-connect-core-1_0.html#ThirdPartyInitiatedLogin.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response object.
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response;

  /**
   * Retrieve access token and ID token.
   *
   * Exchanging the authorization code that is received as the result of the
   * authentication request for an access token and an ID token.
   *
   * The ID token is a cryptographically signed JSON object encoded in base64.
   * It contains identity information about the user.
   * The access token can be sent to the login provider to obtain user profile
   * information.
   *
   * @param string $authorization_code
   *   Authorization code received as a result of the the authorization request.
   *
   * @return array|null
   *   An associative array containing:
   *   - id_token: The ID token that holds user data.
   *   - access_token: Access token that can be used to obtain user profile
   *     information.
   *   - expire: Unix timestamp of the expiration date of the access token.
   *   Or NULL if tokens could not be retrieved.
   */
  public function retrieveTokens(string $authorization_code): ?array;

  /**
   * Retrieves user info: additional user profile data.
   *
   * @param string $access_token
   *   Access token.
   *
   * @return array|null
   *   Additional user profile information or NULL on failure.
   */
  public function retrieveUserInfo(string $access_token): ?array;

  /**
   * Check if the client uses the userinfo endpoint.
   *
   * @return bool
   *   Whether the client uses the userinfo endpoint or not.
   */
  public function usesUserInfo(): bool;

  /**
   * Return the plugin label as defined in the annotation.
   *
   * @return string
   *   Plugin label.
   */
  public function getLabel(): string;

  /**
   * Sets the parent entity ID.
   *
   * @param string $entity_id
   *   The parent entity ID.
   */
  public function setParentEntityId(string $entity_id);

  /**
   * Returns the parent entity ID.
   *
   * @return string
   *   The parent entity ID.
   */
  public function getParentEntityId() : string;

}
