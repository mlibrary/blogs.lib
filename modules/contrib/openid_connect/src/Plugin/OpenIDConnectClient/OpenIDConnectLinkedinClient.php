<?php

namespace Drupal\openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;
use Symfony\Component\HttpFoundation\Response;

/**
 * LinkedIn OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for LinkedIn.
 *
 * @OpenIDConnectClient(
 *   id = "linkedin",
 *   label = @Translation("LinkedIn")
 * )
 */
class OpenIDConnectLinkedinClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $url = 'https://www.linkedin.com/developer/apps';
    $form['description'] = [
      '#markup' => '<div class="description">' . $this->t('Set up your app in <a href="@url" target="_blank">my apps</a> on LinkedIn.', ['@url' => $url]) . '</div>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints(): array {
    return [
      'authorization' => 'https://www.linkedin.com/oauth/v2/authorization',
      'token' => 'https://www.linkedin.com/oauth/v2/accessToken',
      'userinfo' => 'https://api.linkedin.com/v2/me?projection=(id,localizedFirstName,localizedLastName,profilePicture(displayImage~:playableStreams))',
      'useremail' => 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function authorize(string $scope = 'openid email', array $additional_params = []): Response {
    // Use LinkedIn specific authorizations.
    return parent::authorize('r_liteprofile r_emailaddress');
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveUserInfo(string $access_token): ?array {
    $userinfo = [];
    $info = parent::retrieveUserInfo($access_token);

    if ($info) {
      $userinfo['sub'] = $info['id'] ?? '';
      $userinfo['first_name'] = $info['localizedFirstName'] ?? '';
      $userinfo['last_name'] = $info['localizedLastName'] ?? '';
      $userinfo['name'] = $userinfo['first_name'] . ' ' . $userinfo['last_name'];

      if (isset($info['profilePicture']['displayImage~']['elements'])) {
        // The picture was provided.
        $pictures = $info['profilePicture']['displayImage~']['elements'];
        // The last picture should have the largest picture of size 800x800 px.
        $last_picture = end($pictures);

        if (isset($last_picture['identifiers'][0]['identifier'])) {
          $userinfo['picture'] = $last_picture['identifiers'][0]['identifier'];
        }
      }
      else {
        // The picture was not provided.
        $userinfo['picture'] = '';
      }
    }

    // Get the email. It should always be provided.
    if ($email = $this->retrieveUserEmail($access_token)) {
      $userinfo['email'] = $email;
    }

    return $userinfo;
  }

  /**
   * Get user email.
   *
   * @param string $access_token
   *   An access token string.
   *
   * @return string|null
   *   An email or null.
   */
  protected function retrieveUserEmail(string $access_token): ?string {
    $request_options = [
      'headers' => [
        'Authorization' => 'Bearer ' . $access_token,
        'Accept' => 'application/json',
      ],
    ];
    $endpoints = $this->getEndpoints();

    try {
      $response = $this->httpClient->get($endpoints['useremail'], $request_options);
      $object = Json::decode((string) $response->getBody());

      if (isset($object['elements'])) {
        foreach ($object['elements'] as $element) {
          if (isset($element['handle~']['emailAddress'])) {
            // The email address was found.
            return $element['handle~']['emailAddress'];
          }
        }
      }
    }
    catch (\Exception $e) {
      $variables = [
        '@message' => 'Could not retrieve user email information',
        '@error_message' => $e->getMessage(),
      ];
      $this->loggerFactory->get('openid_connect_' . $this->pluginId)
        ->error('@message. Details: @error_message', $variables);
    }

    // No email address was provided.
    return NULL;
  }

}
