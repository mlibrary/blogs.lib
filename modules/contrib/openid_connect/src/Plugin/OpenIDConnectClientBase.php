<?php

namespace Drupal\openid_connect\Plugin;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\openid_connect\OpenIDConnectAutoDiscover;
use Drupal\openid_connect\OpenIDConnectStateTokenInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for OpenID Connect client plugins.
 */
abstract class OpenIDConnectClientBase extends PluginBase implements OpenIDConnectClientInterface, ContainerFactoryPluginInterface {
  use StringTranslationTrait;
  use PluginWithFormsTrait;

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger factory used for logging.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $dateTime;

  /**
   * Page cache kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $pageCacheKillSwitch;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The OpenID state token service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectStateTokenInterface
   */
  protected $stateToken;

  /**
   * The OpenID well-known discovery service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectAutoDiscover
   */
  protected $autoDiscover;

  /**
   * The parent entity identifier.
   *
   * @var string
   */
  protected $parentEntityId;

  /**
   * The constructor.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin identifier.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The http client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Component\Datetime\TimeInterface $datetime_time
   *   The datetime.time service.
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   Policy evaluating to static::DENY when the kill switch was triggered.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\openid_connect\OpenIDConnectStateTokenInterface $state_token
   *   The OpenID state token service.
   * @param \Drupal\openid_connect\OpenIDConnectAutoDiscover $auto_discover
   *   The OpenID well-known discovery service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, RequestStack $request_stack, ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory, TimeInterface $datetime_time, KillSwitch $page_cache_kill_switch, LanguageManagerInterface $language_manager, OpenIDConnectStateTokenInterface $state_token, OpenIDConnectAutoDiscover $auto_discover) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->requestStack = $request_stack;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
    $this->dateTime = $datetime_time;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
    $this->languageManager = $language_manager;
    $this->stateToken = $state_token;
    $this->autoDiscover = $auto_discover;
    $this->parentEntityId = '';
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack'),
      $container->get('http_client'),
      $container->get('logger.factory'),
      $container->get('datetime.time'),
      $container->get('page_cache_kill_switch'),
      $container->get('language_manager'),
      $container->get('openid_connect.state_token'),
      $container->get('openid_connect.autodiscover')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() : string {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $current_configuration = $this->configuration ?: $this->defaultConfiguration();

    $this->configuration = array_merge($current_configuration, $configuration);
  }

  /**
   * Unsets some elements of the configuration.
   *
   * @param array $keys
   *   Array of keys to unset.
   */
  protected function unsetConfigurationKeys(array $keys) {
    foreach ($keys as $key) {
      if (isset($this->configuration[$key])) {
        unset($this->configuration[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'client_id' => '',
      'client_secret' => '',
      'iss_allowed_domains' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntityId(string $entity_id) {
    $this->parentEntityId = $entity_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentEntityId() : string {
    return $this->parentEntityId;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['client_id'] = [
      '#title' => $this->t('Client ID'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['client_id'],
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#title' => $this->t('Client secret'),
      '#type' => 'textarea',
      '#default_value' => $this->configuration['client_secret'],
      '#required' => TRUE,
    ];
    $form['iss_allowed_domains'] = [
      '#title' => $this->t('Allowed domains'),
      '#description' => $this->t('Enter one domain per line that are allowed to initiate SSO using ISS.'),
      '#type' => 'textarea',
      '#default_value' => $this->configuration['iss_allowed_domains'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientScopes(): ?array {
    return ['openid', 'email'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Empty function. Can be overridden by derived classes if required.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Empty function. Can be overridden by derived classes if required.
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    $language_none = \Drupal::languageManager()
      ->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE);

    $redirect_uri = Url::fromRoute(
      'openid_connect.redirect_controller_redirect',
      [
        'openid_connect_client' => $this->parentEntityId,
      ],
      [
        'absolute' => TRUE,
        'language' => $language_none,
      ]
    )->toString(TRUE);

    $url_options = [
      'query' => [
        'client_id' => $this->configuration['client_id'],
        'response_type' => 'code',
        'scope' => $scope,
        'redirect_uri' => $redirect_uri->getGeneratedUrl(),
        'state' => $this->stateToken->generateToken(),
      ],
    ];

    if (!empty($additional_params)) {
      $url_options['query'] = array_merge($url_options['query'], $additional_params);
    }

    $endpoints = $this->getEndpoints();
    // Clear _GET['destination'] because we need to override it.
    $this->requestStack->getCurrentRequest()->query->remove('destination');
    $authorization_endpoint = Url::fromUri($endpoints['authorization'], $url_options)->toString(TRUE);

    $this->loggerFactory->get('openid_connect_' . $this->pluginId)->debug('Send authorize request to @url', ['@url' => $authorization_endpoint->getGeneratedUrl()]);
    $response = new TrustedRedirectResponse($authorization_endpoint->getGeneratedUrl());
    // We can't cache the response, since this will prevent the state to be
    // added to the session. The kill switch will prevent the page getting
    // cached for anonymous users when page cache is active.
    $this->pageCacheKillSwitch->trigger();

    return $response;
  }

  /**
   * Helper function for URL options.
   *
   * @param string $scope
   *   A string of scopes.
   * @param \Drupal\Core\GeneratedUrl $redirect_uri
   *   URI to redirect for authorization.
   *
   * @return array
   *   Array with URL options.
   */
  protected function getUrlOptions(string $scope, GeneratedUrl $redirect_uri): array {
    return [
      'query' => [
        'client_id' => $this->configuration['client_id'],
        'response_type' => 'code',
        'scope' => $scope,
        'redirect_uri' => $redirect_uri->getGeneratedUrl(),
        'state' => $this->stateToken->generateToken(),
      ],
    ];
  }

  /**
   * Helper function for request options.
   *
   * @param string $authorization_code
   *   Authorization code received as a result of the the authorization request.
   * @param string $redirect_uri
   *   URI to redirect for authorization.
   *
   * @return array
   *   Array with request options.
   */
  protected function getRequestOptions(string $authorization_code, string $redirect_uri): array {
    return [
      'form_params' => [
        'code' => $authorization_code,
        'client_id' => $this->configuration['client_id'],
        'client_secret' => $this->configuration['client_secret'],
        'redirect_uri' => $redirect_uri,
        'grant_type' => 'authorization_code',
      ],
      'headers' => [
        'Accept' => 'application/json',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveTokens(string $authorization_code): ?array {
    // Exchange `code` for access token and ID token.
    $redirect_uri = $this->getRedirectUrl()->toString();
    $endpoints = $this->getEndpoints();
    $request_options = $this->getRequestOptions($authorization_code, $redirect_uri);

    $client = $this->httpClient;
    try {
      $response = $client->post($endpoints['token'], $request_options);
      $response_data = Json::decode((string) $response->getBody());

      // Expected result.
      if (is_array($response_data)) {
        $tokens = [];
        if (isset($response_data['id_token'])) {
          $tokens['id_token'] = $response_data['id_token'];
        }
        if (isset($response_data['access_token'])) {
          $tokens['access_token'] = $response_data['access_token'];
        }
        if (array_key_exists('expires_in', $response_data)) {
          $tokens['expire'] = $this->dateTime->getRequestTime() + $response_data['expires_in'];
        }
        if (array_key_exists('refresh_token', $response_data)) {
          $tokens['refresh_token'] = $response_data['refresh_token'];
        }
        return $tokens;
      }
    }
    catch (\Exception $e) {
      $error_message = $e->getMessage();
      if ($e instanceof RequestException && $e->hasResponse()) {
        $error_message .= ' Response: ' . $e->getResponse()->getBody()->getContents();
      }

      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('Could not retrieve tokens. Details: @error_message', ['@error_message' => $error_message]);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo(string $access_token): ?array {
    $request_options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
        'Accept' => 'application/json',
      ],
    ];
    $endpoints = $this->getEndpoints();

    try {
      $response = $this->httpClient->get($endpoints['userinfo'], $request_options);
      $userinfo = Json::decode((string) $response->getBody());

      $this->loggerFactory->get('openid_connect_' . $this->pluginId)->debug('Response from userinfo endpoint: @userinfo',
        ['@userinfo' => print_r($userinfo, TRUE)]);

      return (is_array($userinfo)) ? $userinfo : NULL;
    }
    catch (\Exception $e) {
      $error = $e->getMessage();

      if ($e instanceof RequestException && $e->hasResponse()) {
        $response_body = $e->getResponse()->getBody()->getContents();
        $error .= ' Response: ' . $response_body;
      }

      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('Could not retrieve user profile information. Details: @error_message',
          ['@error_message' => $error]);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function usesUserInfo(): bool {
    return !empty($this->getEndpoints()['userinfo']);
  }

  /**
   * Returns the redirect URL.
   *
   * @param array $route_parameters
   *   See \Drupal\Core\Url::fromRoute() for details.
   * @param array $options
   *   See \Drupal\Core\Url::fromRoute() for details.
   *
   * @return \Drupal\Core\Url
   *   A new Url object for a routed (internal to Drupal) URL.
   *
   * @see \Drupal\Core\Url::fromRoute()
   */
  protected function getRedirectUrl(array $route_parameters = [], array $options = []): Url {
    $route_parameters += ['openid_connect_client' => $this->parentEntityId];
    $options += [
      'absolute' => TRUE,
      'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE),
    ];
    return Url::fromRoute('openid_connect.redirect_controller_redirect', $route_parameters, $options);
  }

}
