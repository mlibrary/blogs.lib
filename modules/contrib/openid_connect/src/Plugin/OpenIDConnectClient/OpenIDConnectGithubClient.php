<?php

namespace Drupal\openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * GitHub OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for GitHub.
 *
 * @OpenIDConnectClient(
 *   id = "github",
 *   label = @Translation("GitHub")
 * )
 */
class OpenIDConnectGithubClient extends OpenIDConnectClientBase {

  /**
   * A mapping of OpenID Connect user claims to GitHub user properties.
   *
   * See https://developer.github.com/v3/users .
   *
   * @var array
   */
  protected $userInfoMapping = [
    'name' => 'name',
    'sub' => 'id',
    'email' => 'email',
    'preferred_username' => 'login',
    'picture' => 'avatar_url',
    'profile' => 'html_url',
    'website' => 'blog',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $url = 'https://github.com/settings/developers';
    $form['description'] = [
      '#markup' => '<div class="description">' . $this->t('Set up your app in <a href="@url" target="_blank">developer applications</a> on GitHub.', ['@url' => $url]) . '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(): array {
    return [
      'authorization' => 'https://github.com/login/oauth/authorize',
      'token' => 'https://github.com/login/oauth/access_token',
      'userinfo' => 'https://api.github.com/user',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    // Use GitHub specific authorisations.
    return parent::authorize('user:email');
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo(string $access_token): ?array {
    $request_options = [
      'headers' => [
        'Authorization' => 'token ' . $access_token,
        'Accept' => 'application/json',
      ],
    ];
    $endpoints = $this->getEndpoints();

    $client = $this->httpClient;
    try {
      $claims = [];
      $response = $client->get($endpoints['userinfo'], $request_options);
      $response_data = Json::decode((string) $response->getBody());

      foreach ($this->userInfoMapping as $claim => $key) {
        if (array_key_exists($key, $response_data)) {
          $claims[$claim] = $response_data[$key];
        }
      }

      // GitHub names can be empty. Fall back to the login name.
      if (empty($claims['name']) && isset($response_data['login'])) {
        $claims['name'] = $response_data['login'];
      }

      // Convert the updated_at date to a timestamp.
      if (!empty($response_data['updated_at'])) {
        $claims['updated_at'] = strtotime($response_data['updated_at']);
      }

      // The email address is only provided in the User resource if the user has
      // chosen to display it publicly. So we need to make another request to
      // find out the user's email address(es).
      if (empty($claims['email'])) {
        $email_response = $client->get($endpoints['userinfo'] . '/emails', $request_options);
        $email_response_data = Json::decode((string) $email_response->getBody());

        foreach ($email_response_data as $email) {
          // See https://developer.github.com/v3/users/emails/
          if (!empty($email['primary'])) {
            $claims['email'] = $email['email'];
            $claims['email_verified'] = $email['verified'];
            break;
          }
        }
      }

      return $claims;
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
