<?php

namespace Drupal\openid_connect\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto login process.
 *
 * When user is requesting user login, register or password reset page as
 * anonymous, the OpenID Connect client login process should auto start.
 *
 * Login auto start can be disabled in configuration of plugin and will only
 * start if only one OpenID Connect client is enabled.
 *
 * If an anonymous user requests a page with 'showcore' parameter set, standard
 * Drupal login page should be displayed instead of OpenID Connect client login
 * page.
 */
class OpenIDConnectAutoLogin implements EventSubscriberInterface {

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * OpenID Connect Client Plugin Manager.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * OpenID Client to use in login process.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface
   */
  protected $client;

  /**
   * The logger factory.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The OpenID Connect session.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $oidcSession;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Current user.
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $oidcSession
   *   The openid_connect.session service.
   */
  public function __construct(
    AccountInterface $user,
    OpenIDConnectClientManager $plugin_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory,
    OpenIDConnectClaims $claims,
    OpenIDConnectSessionInterface $oidcSession,
  ) {
    $this->currentUser = $user;
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('openid_connect');
    $this->claims = $claims;
    $this->oidcSession = $oidcSession;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['login'];
    return $events;
  }

  /**
   * Auto start OpenID Connect client login process.
   *
   * The process will start, if there is only one client enabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function login(RequestEvent $event): void {
    $request = $event->getRequest();
    if (
      $this->isAutostartEnabled() &&
      $this->currentUser->isAnonymous() &&
      $this->isLoginRequested($request)
    ) {
      if (!$this->hasErrors($request) && !$this->bypassAutoLogin($request)) {
        // Start OpenID Connect login process.
        $this->oidcSession->saveDestination();
        $this->oidcSession->saveOp('login');
        $client = $this->getClient();
        if ($client) {
          $this->logger->debug('Bypassing login with %client client: %parentEntityId', [
            '%client' => $client->getPluginId(),
            '%parentEntityId' => $client->getParentEntityId(),
          ]);
          $scopes = $this->claims->getScopes($client);
          $response = $client->authorize($scopes);
          $event->setResponse($response);
        }
      }
    }
  }

  /**
   * Detect if there is error during OpenID Connect login process.
   *
   * @return bool
   *   TRUE in case of error, FALSE otherwise.
   */
  protected function hasErrors(Request $request): bool {
    if ($request->getSession()->has('messages')) {
      $messages = $request->getSession()->get('messages');
      if (isset($messages['error'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Check if auto start login process is enabled.
   *
   * Autostart means that if a user tries to access login, register, or reset
   * password pages as anonymous, they will be redirected to the OpenID Connect
   * client login process.
   *
   * This function also checks if OpenID client configuration has been provided.
   *
   * @return bool
   *   TRUE if autostart login is enabled, FALSE otherwise.
   */
  protected function isAutostartEnabled(): bool {
    // Check if autostart is enabled.
    $auto_start = (bool) $this->configFactory
      ->get('openid_connect.settings')
      ->get('autostart_login');
    if ($auto_start) {
      $client = $this->getClient();
      // Check if client authorization endpoint is configured.
      if ($client) {
        $endpoints = $client->getEndpoints();
        $auto_start = !empty($endpoints['authorization']);
      }
    }
    return $auto_start;
  }

  /**
   * Check if login or register page was requested.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return bool
   *   TRUE if login or register page was requested, FALSE otherwise.
   */
  protected function isLoginRequested(Request $request): bool {
    // Get route name of current page.
    $route_name = $request->get(RouteObjectInterface::ROUTE_NAME);
    // If the route name is empty, return true to prevent further actions, as
    // it's not yet known what page is being viewed.
    return !empty($route_name) && in_array($route_name, [
      'user.login',
      'user.register',
      'user.pass',
    ]);
  }

  /**
   * Set OpenID Connect Client.
   *
   * Get all OpenID Connect client definitions and return the one to use to log
   * in. If there is more than one client enabled, return null.
   *
   * @return \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface|null
   *   The client or NULL.
   */
  protected function getClient(): ?OpenIDConnectClientInterface {
    if (!$this->client) {
      // Find enabled OpenID Connect clients.
      $clientConfigs = array_filter($this->configFactory->listAll(), function ($var) {
        return str_starts_with($var, 'openid_connect.client.');
      });

      // If there is more than one enabled client, skip the auto-login process.
      if (!$clientConfigs || count($clientConfigs) > 1) {
        return $this->client;
      }

      $clientConfig = $this->configFactory->get(current($clientConfigs));
      $this->client = $this->pluginManager->createInstance($clientConfig->get('plugin'), $clientConfig->get('settings'));
      $this->client->setParentEntityId($clientConfig->get('id'));
    }
    return $this->client;
  }

  /**
   * Check if OpenID connect or Drupal login process were requested.
   *
   * If the Drupal login/register/password reset page should be displayed, the
   * query contains the 'showcore' parameter in the request. Otherwise the login
   * process is starting.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return bool
   *   TRUE if regular Drupal login process should start, FALSE otherwise.
   */
  protected function bypassAutoLogin(Request $request): bool {
    return $request->query->has('showcore');
  }

}
