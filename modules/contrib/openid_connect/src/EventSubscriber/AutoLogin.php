<?php

namespace Drupal\openid_connect\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Auto login process.
 *
 * When user is requesting user login, register or password reset
 * page as anonymous, OpenID Connect client login process should auto start.
 *
 * Login auto start can be disabled in configuration of plugin and
 * will only start, if only one OpenID Connect client is enabled.
 *
 * If user, as anonymous will request page with 'openid_connect_bypass'
 * parameter, standard Drupal login page should be displayed
 * instead of OpenID Connect client login page.
 */
class AutoLogin implements EventSubscriberInterface {

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
   * The constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   Current user.
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(AccountInterface $user, OpenIDConnectClientManager $plugin_manager, ConfigFactoryInterface $config_factory) {
    $this->currentUser = $user;
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => [
        ['login', 28],
      ],
    ];
  }

  /**
   * Auto start OpenID Connect client login process.
   *
   * The process will start, if there is only one client enabled.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function login(RequestEvent $event) {
    // Get current request.
    $request = $event->getRequest();
    // Check if user is anonymous and login or register page was requested.
    if ($this->isAutostartEnabled() && $this->currentUser->isAnonymous() && $this->isLoginRequested($request)) {
      // If there is no login errors and login process is not in progress
      // and openid_connect_bypass is not provided, then start login process.
      if (!$this->hasErrors() && empty($request->query->get('showcore'))) {
        // Start OpenID Connect login process.
        \Drupal::service('openid_connect.session')->saveDestination();
        $_SESSION['openid_connect_op'] = 'login';
        $client = $this->getClient();
        if ($client) {
          $response = $client->authorize();
          // Redirect to given response.
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
  protected function hasErrors() {
    if (isset($_SESSION['messages']) && isset($_SESSION['messages']['error'])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if auto start login process is enabled.
   *
   * Autostart means, that if user tries to access login, register or reset
   * password pages as anonymous, it will be redirected to OpenID Connect
   * client login process.
   *
   * This function also checks if openid client configuration has been provided.
   *
   * @return bool
   *   TRUE if autostart login is enabled, FALSE otherwise.
   */
  protected function isAutostartEnabled() {
    // Check if autostart is enabled.
    $auto_start = (bool) $this->configFactory
      ->get('openid_connect.settings')
      ->get('autostart_login');
    if ($auto_start) {
      $client = $this->getClient();
      // Check if client endpoints are configured.
      if ($client) {
        foreach ($client->getEndpoints() as $endpoint) {
          if ($endpoint === NULL) {
            return FALSE;
          }
        }
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
  protected function isLoginRequested(Request $request) {
    // Get route name of current page.
    $route_name = $request->get(RouteObjectInterface::ROUTE_NAME);
    // If route name is empty, return true to prevent further actions,
    // as we don't know yet page, we are viewing.
    return !empty($route_name) && in_array($route_name, [
      'user.login',
      'user.register',
      'user.pass',
    ]);
  }

  /**
   * Set OpenID Connect Client.
   *
   * Get all definitions of OpenID Connect clients and return the one,
   * we should use in auto start login process. If there is more than one
   * clients enabled, return null.
   *
   * @return null|\Drupal\openid_connect\Plugin\OpenIDConnectClientInterface
   *   NULL if no client or client object.
   */
  protected function getClient() {
    // If client isset, don't do that again.
    if (!$this->client) {
      // Find enabled OpenID Connect clients.
      foreach ($this->pluginManager->getDefinitions() as $client_name => $client_plugin) {
        // Get plugin configuration.
        $configuration = $this->configFactory
          ->get('openid_connect.settings.' . $client_name);
        // Check if plugin is enabled.
        if ((bool) $configuration->get('enabled')) {
          // Check if client is not set yet.
          if (!$this->client) {
            // Set enabled client as one to use in auto login process.
            $this->client = $this->pluginManager->createInstance(
              $client_name,
              $configuration->get('settings')
            );
          }
          // If there is more than one enabled client,
          // we can't auto start login process.
          else {
            $this->client = NULL;
            break;
          }
        }
      }
    }
    return $this->client;
  }

}
