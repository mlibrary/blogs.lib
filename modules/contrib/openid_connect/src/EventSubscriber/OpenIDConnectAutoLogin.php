<?php

namespace Drupal\openid_connect\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
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
 * If user, as anonymous will request page with 'showcore'
 * parameter, standard Drupal login page should be displayed
 * instead of OpenID Connect client login page.
 */
class OpenIDConnectAutoLogin implements EventSubscriberInterface
{

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
     * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
     */
    protected $logger;

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
     *
     */
    public function __construct(AccountInterface $user, OpenIDConnectClientManager $plugin_manager, ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory)
    {
        $this->currentUser = $user;
        $this->pluginManager = $plugin_manager;
        $this->configFactory = $config_factory;
        $this->logger = $logger_factory->get('openid_connect');
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
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
     * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
     *   Response event.
     */
    public function login(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        if ($this->isAutostartEnabled() && $this->currentUser->isAnonymous() && $this->isLoginRequested($request)) {
            if (!$this->hasErrors() && !$this->bypassAutoLogin($request)) {
                // Start OpenID Connect login process.
                \Drupal::service('openid_connect.session')->saveDestination();
                $_SESSION['openid_connect_op'] = 'login';
                $client = $this->getClient();
                if ($client) {
                    $this->logger->debug(
                        'Bypassing login with %client client: %parentEntityId', [
                        '%client' => $client->getPluginId(),
                        '%parentEntityId' => $client->getParentEntityId()
                    ]);
                    $response = $client->authorize();
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
    protected function hasErrors(): bool
    {
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
    protected function isAutostartEnabled(): bool
    {
        // Check if autostart is enabled.
        $auto_start = (bool)$this->configFactory
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
    protected function isLoginRequested(Request $request): bool
    {
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
    protected function getClient(): ?\Drupal\openid_connect\Plugin\OpenIDConnectClientInterface
    {
        if (!$this->client) {
            // Find enabled OpenID Connect clients.
            $clientConfigs = array_filter($this->configFactory->listAll(), function ($var) {
                return str_starts_with($var, 'openid_connect.client.');
            });

            // If there is more than one enabled client, we can't auto start login process.
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
     * If we should display Drupal login/register/password reset page,
     * the query contains the 'showcore' parameter in the request. Otherwise we are
     * starting OpenID Connect client login process.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *   Request.
     *
     * @return bool
     *   TRUE if regular Drupal login process should start, FALSE otherwise.
     */
    protected function bypassAutoLogin(Request $request): bool
    {
        return $request->query->has('showcore');
    }

}
