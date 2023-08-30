<?php

namespace Drupal\openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Facebook OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for Facebook.
 *
 * @OpenIDConnectClient(
 *   id = "facebook",
 *   label = @Translation("Facebook")
 * )
 */
class OpenIDConnectFacebookClient extends OpenIDConnectClientBase {

  /**
   * Facebook API versions.
   *
   * @var array
   */
  protected $versions = [
    'v2.12', 'v2.11', 'v2.10', 'v2.9', 'v2.8', 'v2.7', 'v2.6', 'v2.5', 'v2.4', 'v2.3',
  ];

  /**
   * Facebook fields.
   *
   * @var array
   */
  protected $fields = [
    'id', 'name', 'email', 'first_name', 'last_name', 'gender', 'locale', 'timezone', 'picture.height(500)',
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'api_version' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['api_version'] = [
      '#title' => $this->t('API Version'),
      '#type' => 'select',
      '#options' => array_combine($this->versions, $this->versions),
      '#default_value' => $this->configuration['api_version'],
    ];
    $url = 'https://developers.facebook.com/apps/';
    $form['description'] = [
      '#markup' => '<div class="description">' . $this->t('Set up your app in <a href="@url" target="_blank">my apps</a> on Facebook.', ['@url' => $url]) . '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(): array {
    return [
      'authorization' => 'https://www.facebook.com/' . $this->configuration['api_version'] . '/dialog/oauth',
      'token' => 'https://graph.facebook.com/' . $this->configuration['api_version'] . '/oauth/access_token',
      'userinfo' => 'https://graph.facebook.com/' . $this->configuration['api_version'] . '/me',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    // Use Facebook specific authorisations.
    return parent::authorize('public_profile email');
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo(string $access_token): ?array {
    $request_options = [
      'query' => [
        'access_token' => $access_token,
        'fields' => implode(',', $this->fields),
      ],
      'headers' => [
        'Accept' => 'application/json',
      ],
    ];
    $endpoints = $this->getEndpoints();

    /** @var \GuzzleHttp\Client $client */
    $client = $this->httpClient;
    try {
      $response = $client->get($endpoints['userinfo'], $request_options);
      $userinfo = Json::decode((string) $response->getBody());

      // Make sure the result is an array before returning it.
      if (is_array($userinfo)) {
        $userinfo['sub'] = $userinfo['id'];
        if (!empty($userinfo['picture']['data']['url'])) {
          $userinfo['picture'] = $userinfo['picture']['data']['url'];
        }
        return $userinfo;
      }
    }
    catch (\Exception $e) {
      $variables = [
        '@message' => 'Could not retrieve user profile information',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
    }
    return NULL;
  }

}
