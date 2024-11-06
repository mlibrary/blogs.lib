<?php

namespace Drupal\openid_connect\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\externalauth\AuthmapInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\OpenIDConnectStateTokenInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirect controller.
 *
 * @package Drupal\openid_connect\Controller
 */
class OpenIDConnectRedirectController implements ContainerInjectionInterface, AccessInterface {

  use LoggerChannelTrait;
  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The OpenID state token service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectStateTokenInterface
   */
  protected $stateToken;

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The OpenID Connect service.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIDConnect;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The external authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The constructor.
   *
   * @param \Drupal\openid_connect\OpenIDConnect $openid_connect
   *   The OpenID Connect service.
   * @param \Drupal\openid_connect\OpenIDConnectStateTokenInterface $state_token
   *   The OpenID state token service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The external authmap service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Account proxy for the currently logged-in user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID claims service.
   */
  public function __construct(OpenIDConnect $openid_connect, OpenIDConnectStateTokenInterface $state_token, RequestStack $request_stack, OpenIDConnectSessionInterface $session, ConfigFactoryInterface $config_factory, AuthmapInterface $authmap, AccountProxyInterface $current_user, ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, OpenIDConnectClaims $claims) {
    $this->openIDConnect = $openid_connect;
    $this->stateToken = $state_token;
    $this->requestStack = $request_stack;
    $this->session = $session;
    $this->configFactory = $config_factory;
    $this->authmap = $authmap;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->claims = $claims;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectRedirectController {
    return new static(
      $container->get('openid_connect.openid_connect'),
      $container->get('openid_connect.state_token'),
      $container->get('request_stack'),
      $container->get('openid_connect.session'),
      $container->get('config.factory'),
      $container->get('externalauth.authmap'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('openid_connect.claims')
    );
  }

  /**
   * Access callback: Initiate page.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $openid_connect_client
   *   The client.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the acting user is allowed to initiate the authorization.
   */
  public function accessInitiate(OpenIDConnectClientEntityInterface $openid_connect_client): AccessResultInterface {
    // Only the anonymous user should be able to access redirects.
    if ($this->currentUser->isAuthenticated()) {
      return AccessResult::forbidden();
    }

    if (empty($openid_connect_client)) {
      return AccessResult::forbidden();
    }

    $query = $this->requestStack->getCurrentRequest()->query;

    // If the iss query parameter exists, the user is attempting SSO directly
    // from the IDP.
    $iss = $query->get('iss');
    if (!empty($iss)) {
      // If the iss domain is not in the allowed list, return forbidden.
      if (!$this->isValidRedirect($openid_connect_client, $iss)) {
        return AccessResult::forbidden();
      }

      return AccessResult::allowed();
    }

    // Default to forbidden.
    return AccessResult::forbidden();
  }

  /**
   * Access callback: Redirect page.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public function accessAuthenticate(): AccessResultInterface {
    // Confirm anti-forgery state token. This round-trip verification helps to
    // ensure that the user, not a malicious script, is making the request.
    $request = $this->requestStack->getCurrentRequest();
    $state_token = $request->get('state');
    if ($state_token && $this->stateToken->confirm($state_token)) {
      return AccessResult::allowed();
    }

    // Default to forbidden.
    return AccessResult::forbidden();
  }

  /**
   * Redirect.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $openid_connect_client
   *   The client.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response starting the authentication request.
   *
   * @throws \Exception
   */
  public function authenticate(OpenIDConnectClientEntityInterface $openid_connect_client): RedirectResponse {
    $request = $this->requestStack->getCurrentRequest();

    // Delete the state token, since it's already been confirmed.
    $this->session->retrieveStateToken();

    // Get parameters from the session, and then clean up.
    $params = $this->session->retrieveOp();
    $op = $params['op'] ?? 'login';
    $uid = $params['uid'] ?? NULL;

    $plugin = $openid_connect_client->getPlugin();
    if (!$request->get('error') && (!($plugin instanceof OpenIDConnectClientInterface) || !$request->get('code'))) {
      // In case we don't have an error, but the client could not be loaded or
      // there is no state token specified, the URI is probably being visited
      // outside of the login flow.
      throw new NotFoundHttpException();
    }

    $provider_param = ['@provider' => $openid_connect_client->label()];

    if ($request->get('error')) {
      if (in_array($request->get('error'), [
        'interaction_required',
        'login_required',
        'account_selection_required',
        'consent_required',
      ])) {
        // If we have an one of the above errors, that means the user hasn't
        // granted the authorization for the claims.
        $this->messenger()->addWarning($this->t('Logging in with @provider has been canceled.', $provider_param));
      }
      else {
        // Any other error should be logged. E.g. invalid scope.
        $variables = [
          '@error' => $request->get('error'),
          '@details' => $request->get('error_description') ? $request->get('error_description') : $this->t('Unknown error.'),
        ];
        $message = 'Authorization failed: @error. Details: @details';
        $this->getLogger('openid_connect_' . $openid_connect_client->getPluginId())->error($message, $variables);
        $this->messenger()->addError($this->t('Could not authenticate with @provider.', $provider_param));
      }
    }
    else {
      // Process the login or connect operations.
      $tokens = $plugin->retrieveTokens($request->get('code'));
      if ($tokens) {
        if ($op === 'login') {
          $success = $this->openIDConnect->completeAuthorization($openid_connect_client, $tokens);

          if (!$success) {
            $this->messenger()->addError($this->t('Logging in with @provider could not be completed due to an error.', $provider_param));
          }
        }
        elseif (($op === 'connect') && ($uid === (int) $this->currentUser->id())) {
          $success = $this->openIDConnect->connectCurrentUser($openid_connect_client, $tokens);
          if ($success) {
            $this->messenger()->addMessage($this->t('Account successfully connected with @provider.', $provider_param));
          }
          else {
            $this->messenger()->addError($this->t('Connecting with @provider could not be completed due to an error.', $provider_param));
          }
        }
      }
      else {
        $this->messenger()->addError($this->t('Failed to get authentication tokens for @provider. Check logs for further details.', $provider_param));
      }
    }

    // The destination parameter should be a prepared uri and include any query
    // parameters or fragments already.
    //
    // @see \Drupal\openid_connect\OpenIDConnectSessionInterface::saveDestination()
    $session = $this->session->retrieveDestination();
    $destination = $session['destination'] ?: $this->configFactory->get('openid_connect.settings')->get('redirect_login');
    $langcode = $session['langcode'] ?: $this->languageManager->getCurrentLanguage()->getId();
    $language = $this->languageManager->getLanguage($langcode);

    $redirect = Url::fromUri('internal:/' . ltrim($destination, '/'), ['language' => $language])->toString();
    return new RedirectResponse($redirect);
  }

  /**
   * Initiate an SSO from the IDP.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $openid_connect_client
   *   The client.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A redirect response back to the IDP.
   */
  public function initiate(OpenIDConnectClientEntityInterface $openid_connect_client): Response {
    if (empty($openid_connect_client)) {
      throw new AccessDeniedHttpException();
    }

    // Handle the ISS query parameter.
    $query = $this->requestStack->getCurrentRequest()->query;
    $iss = $query->get('iss');

    $additional_parameters = [];

    // ISS is required.
    if (empty($iss)) {
      throw new AccessDeniedHttpException();
    }

    // If the iss domain is not in the allowed list, return access denied.
    if (!$this->isValidRedirect($openid_connect_client, $iss)) {
      throw new AccessDeniedHttpException();
    }

    // Handle the login_hint parameter.
    $login_hint = $query->get('login_hint');
    if (!empty($login_hint)) {
      $additional_parameters['login_hint'] = $login_hint;
    }

    // Handle the target_link_uri parameter.
    if (!empty($query->get('target_link_uri'))) {
      $this->session->saveTargetLinkUri($query->get('target_link_uri'));
    }

    $scopes = $this->claims->getScopes($openid_connect_client->getPlugin());
    return $openid_connect_client->getPlugin()->authorize($scopes, $additional_parameters);
  }

  /**
   * Validate if the domain that initiated the SSO is valid.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client
   *   The client that was requested.
   * @param string $iss
   *   The iss url parameter the requested SSO.
   *
   * @return bool
   *   True if the domain is in the allowed list.
   */
  private function isValidRedirect(OpenIDConnectClientEntityInterface $client, string $iss): bool {
    $settings = $client->get('settings');
    if (empty($settings['iss_allowed_domains'])) {
      return FALSE;
    }

    // Get the domains that are allowed.
    $allowed_domains = explode("\r\n", $settings['iss_allowed_domains']);

    $url = parse_url($iss);

    if (empty($url['host'])) {
      return FALSE;
    }

    if (in_array($url['host'], $allowed_domains)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Redirect after logout.
   */
  public function redirectLogout() {
    // Set default URL.
    $language = $this->languageManager->getCurrentLanguage();
    $default_url = Url::fromRoute('<front>', [], ['language' => $language])->toString(TRUE);
    $response = new RedirectResponse($default_url->getGeneratedUrl());

    // @todo The fact that the user has a connected account doesn't necessarily
    //   mean that it was used for the login. This info should probably be kept
    //   in the session.
    // Get client names for this user based on its username.
    $mapped_users = $this->authmap->getAll($this->currentUser->id());
    if (is_array($mapped_users) & !empty($mapped_users)) {
      foreach (array_keys($mapped_users) as $key) {
        // strlen('openid_connect.') = 15.
        $client_name = substr($key, 15);

        // Perform log out.
        if (!empty($client_name)) {
          /** @var \Drupal\openid_connect\Entity\OpenIDConnectClientEntity $entity */
          $entity = current($this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name]));
          if ($entity) {
            $endpoints = $entity->getPlugin()->getEndpoints();

            $config = $this->configFactory->get('openid_connect.settings');

            $redirect_logout = $config->get('redirect_logout');
            $redirect_logout_url = empty($redirect_logout) ? FALSE : Url::fromUri('internal:/' . ltrim($redirect_logout, '/'), ['language' => $language]);

            // Destroy session if provider supports it.
            $end_session_enabled = $config->get('end_session_enabled') ?? FALSE;
            if ($end_session_enabled && !empty($endpoints['end_session'])) {
              $url_options = [
                'query' => ['id_token_hint' => $this->session->retrieveIdToken()],
              ];
              if ($redirect_logout_url) {
                $url_options['query']['post_logout_redirect_uri'] = $redirect_logout_url->setAbsolute()->toString(TRUE)->getGeneratedUrl();
              }
              $redirect = Url::fromUri($endpoints['end_session'], $url_options)->toString(TRUE);
              $response = new TrustedRedirectResponse($redirect->getGeneratedUrl());
              $response->addCacheableDependency($redirect);
            }
            else {
              if (!$end_session_enabled) {
                $this->messenger()->addWarning($this->t('@provider does not support log out. You are logged out of this site but not out of the OpenID Connect provider.', ['@provider' => $entity->label()]));
              }
              if ($redirect_logout_url) {
                $url = $redirect_logout_url->toString(TRUE)->getGeneratedUrl();
                $response = new TrustedRedirectResponse($url);
                $response->addCacheableDependency($url);
              }
            }
            $rsp = ['response' => &$response];
            $context = ['client' => $client_name];
            $this->moduleHandler->alter('openid_connect_redirect_logout', $rsp, $context);
          }
        }
      }
    }
    // Logout from Drupal.
    user_logout();
    return $response;
  }

}
