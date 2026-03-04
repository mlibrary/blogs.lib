<?php

declare(strict_types=1);

namespace Drupal\openid_connect\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\externalauth\AuthmapInterface;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handle custom logouts with OpenID Connect.
 */
class LogoutService {

  use StringTranslationTrait;

  /**
   * Construct a logout service class.
   */
  public function __construct(
    protected readonly ConfigFactoryInterface $configFactory,
    protected readonly AuthmapInterface $authmap,
    protected readonly AccountProxyInterface $currentUser,
    protected readonly ModuleHandlerInterface $moduleHandler,
    protected readonly LanguageManagerInterface $languageManager,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly OpenIDConnectSessionInterface $session,
    protected readonly LoggerChannelFactoryInterface $loggerChannelFactory,
  ) {
  }

  /**
   * Get the redirect response to be used when a member logs out.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect to the provider and/or the internal redirect.
   */
  public function getLogoutRedirectResponse(): RedirectResponse {
    $response = $this->getDefaultResponse();
    // If both end session and logout redirect are disabled, return the default.
    if (
      !$this->isEndSessionEnabled() &&
      !$this->isLogoutRedirectEnabled()
    ) {
      return $response;
    }

    // If the user isn't mapped to any OpenID Clients, return the default.
    if (!$this->hasMappedUsers()) {
      return $response;
    }

    // Get the openid connect client used for the login.
    $provider = $this->getLoginProvider();
    // Guard the provider. If the user is not connected to OpenID
    // then we want to default the logout.
    if (is_null($provider)) {
      return $response;
    }
    $logoutRedirectUrl = $this->getLogoutRedirectUrl();

    // Default the response to the home page.
    $response = new TrustedRedirectResponse('internal:/<front>');

    // If the logout redirect is enabled, set the default redirect.
    if ($this->isLogoutRedirectEnabled()) {
      $redirectUrl = $logoutRedirectUrl->toString(TRUE)->getGeneratedUrl();
      $response->setTrustedTargetUrl($redirectUrl);
      $response->addCacheableDependency($redirectUrl);
    }

    if (
      $this->isEndSessionEnabled() &&
      $this->providerHasEndSessionEndpoint($provider)
    ) {
      // This will override the redirect only, which is expected.
      $urlOptions = [
        'query' => ['id_token_hint' => $this->session->retrieveIdToken()],
      ];
      if ($logoutRedirectUrl) {
        $urlOptions['query']['post_logout_redirect_uri'] = $logoutRedirectUrl->setAbsolute()->toString(TRUE)->getGeneratedUrl();
      }
      $redirectUrl = Url::fromUri($this->getProviderEndSessionEndpoint($provider), $urlOptions)->toString(TRUE);
      $response = new TrustedRedirectResponse($redirectUrl->getGeneratedUrl());
      $response->addCacheableDependency($redirectUrl);
    }

    // If the end session is expected and the provider doesn't
    // have an endpoint configured, write to the logs of a misconfiguration.
    if (
      $this->isEndSessionEnabled() &&
      !$this->providerHasEndSessionEndpoint($provider)
    ) {
      // Alert the logs of a misconfiguration.
      $this->loggerChannelFactory->get('openid_connect')->warning(
        sprintf('%s does not support log out. Drupal session was expired, but the session at the identity provider remains.', $provider->label())
      );
    }

    $clientName = $provider?->getPlugin()?->getPluginId() ?? 'unknown';

    $rsp = ['response' => &$response];
    $context = ['client' => $clientName];
    $this->moduleHandler->alter('openid_connect_redirect_logout', $rsp, $context);

    return $response;
  }

  /**
   * Default any redirects to the home page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A default redirect response.
   */
  protected function getDefaultResponse(): RedirectResponse {
    $language = $this->languageManager->getCurrentLanguage();
    $default_url = Url::fromRoute('<front>', [], ['language' => $language])->toString(TRUE);
    return new RedirectResponse($default_url->getGeneratedUrl());
  }

  /**
   * Check if the redirect logout setting is enabled.
   *
   * @return bool
   *   True if the redirect logout setting has a value.
   */
  protected function isLogoutRedirectEnabled(): bool {
    return !empty($this->configFactory->get('openid_connect.settings')->get('redirect_logout'));
  }

  /**
   * Get the redirect logout value as a Url.
   *
   * @return \Drupal\Core\Url|null
   *   The redirect logout value transformed as a Url object.
   */
  protected function getLogoutRedirectUrl(): ?Url {
    $redirectLogout = $this->configFactory->get('openid_connect.settings')->get('redirect_logout');
    if (empty($redirectLogout)) {
      return NULL;
    }

    return Url::fromUri(sprintf('internal:/%s', ltrim($redirectLogout, '/')), ['language' => $this->languageManager->getCurrentLanguage()]);
  }

  /**
   * Check if the end session from provider setting is enabled.
   *
   * @return bool
   *   True if the end session configuration is enabled.
   */
  protected function isEndSessionEnabled(): bool {
    return $this->configFactory->get('openid_connect.settings')->get('end_session_enabled') ?? FALSE;
  }

  /**
   * Check if the provider has an end session endpoint defined.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $provider
   *   The open id provider to check for an end session endpoint.
   *
   * @return bool
   *   True if the provider has a value defined for the end session endpoint.
   */
  protected function providerHasEndSessionEndpoint(OpenIDConnectClientEntityInterface $provider): bool {
    // Pull the end_session endpoint from the endpoint array.
    ['end_session' => $end_session_endpoint] = $provider->getPlugin()->getEndpoints();
    return !empty($end_session_endpoint);
  }

  /**
   * Get the end session endpoint from the provider.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $provider
   *   The open id provider to retrieve an end session endpoint.
   *
   * @return string|null
   *   The endpoint if defined, otherwise null.
   */
  protected function getProviderEndSessionEndpoint(OpenIDConnectClientEntityInterface $provider): ?string {
    // Pull the end_session endpoint from the endpoints array.
    ['end_session' => $end_session_endpoint] = $provider->getPlugin()->getEndpoints();
    // Return the endpoint if available.
    return !empty($end_session_endpoint) ? $end_session_endpoint : NULL;
  }

  /**
   * Does openid connect have mapped data for the currently logged in user.
   *
   * @return bool
   *   True if the user has logged in with an openid connect client.
   */
  protected function hasMappedUsers(): bool {
    $test = $this->getMappedUsers();
    return !empty($test);
  }

  /**
   * Get all openid connect user mappings for the logged in user.
   *
   * @return array
   *   All openid connect user mappings for the logged in user.
   */
  protected function getMappedUsers(): array {
    return $this->authmap->getAll($this->currentUser->id());
  }

  /**
   * Get the assumed provider that the user logged in with.
   *
   * @return \Drupal\openid_connect\OpenIDConnectClientEntityInterface|null
   *   The openid connect provider or null if not found.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getLoginProvider(): ?OpenIDConnectClientEntityInterface {
    // @todo The fact that the user has a connected account doesn't necessarily
    // mean that it was used for the login. This info should probably be kept
    // in the session.
    $provider = NULL;
    foreach (array_keys($this->getMappedUsers()) as $mappedUserKey) {
      // Removing the 'openid_connect.' prefix (which is 15 characters long)
      // This will provide the client name as it was stored in the
      // external authmap table.
      $client_name = substr($mappedUserKey, 15);
      if (empty($client_name)) {
        continue;
      }
      $entities = $this->entityTypeManager
        ->getStorage('openid_connect_client')
        ->loadByProperties(['id' => $client_name]);

      // If there is a provider, set it and break the loop.
      if (!empty($entities)) {
        $provider = current($entities);
        break;
      }
    }

    return $provider;
  }

}
