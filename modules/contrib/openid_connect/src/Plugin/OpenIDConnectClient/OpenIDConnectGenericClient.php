<?php

namespace Drupal\openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * Generic OAuth 2.0 OpenID Connect client.
 *
 * Used primarily to login to Drupal sites powered by oauth2_server or PHP
 * sites powered by oauth2-server-php.
 *
 * @OpenIDConnectClient(
 *   id = "generic",
 *   label = @Translation("Generic OAuth 2.0")
 * )
 */
class OpenIDConnectGenericClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'issuer_url' => '',
      'authorization_endpoint' => 'https://example.com/oauth2/authorize',
      'token_endpoint' => 'https://example.com/oauth2/token',
      'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
      'end_session_endpoint' => '',
      'scopes' => ['openid', 'email'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['use_well_known'] = [
      '#title' => $this->t('Auto discover endpoints'),
      '#type' => 'checkbox',
      '#description' => $this->t(
        'Requires IDP support for "<a href="@url" target="_blank">OpenID Connect Discovery</a>".',
        ['@url' => 'https://openid.net/specs/openid-connect-discovery-1_0.html']
      ),
      '#default_value' => !empty($this->configuration['issuer_url']),
    ];

    // Auto discover fields.
    $form['issuer_url'] = [
      '#title' => $this->t('Issuer URL'),
      '#type' => 'url',
      '#default_value' => $this->configuration['issuer_url'],
      '#states' => [
        'visible' => [':input[name="settings[use_well_known]"]' => ['checked' => TRUE]],
      ],
    ];

    $form['authorization_endpoint'] = [
      '#title' => $this->t('Authorization endpoint'),
      '#type' => 'url',
      '#default_value' => $this->configuration['authorization_endpoint'],
      '#states' => [
        'visible' => [':input[name="settings[use_well_known]"]' => ['checked' => FALSE]],
      ],
    ];
    $form['token_endpoint'] = [
      '#title' => $this->t('Token endpoint'),
      '#type' => 'url',
      '#default_value' => $this->configuration['token_endpoint'],
      '#states' => [
        'visible' => [':input[name="settings[use_well_known]"]' => ['checked' => FALSE]],
      ],
    ];
    $form['userinfo_endpoint'] = [
      '#title' => $this->t('UserInfo endpoint'),
      '#type' => 'url',
      '#default_value' => $this->configuration['userinfo_endpoint'],
      '#states' => [
        'visible' => [':input[name="settings[use_well_known]"]' => ['checked' => FALSE]],
      ],
    ];
    $form['end_session_endpoint'] = [
      '#title' => $this->t('End Session endpoint'),
      '#type' => 'url',
      '#default_value' => $this->configuration['end_session_endpoint'],
      '#states' => [
        'visible' => [':input[name="settings[use_well_known]"]' => ['checked' => FALSE]],
      ],
    ];

    $form['scopes'] = [
      '#title' => $this->t('Scopes'),
      '#type' => 'textfield',
      '#description' => $this->t('Custom scopes, separated by spaces, for example: openid email'),
      '#default_value' => implode(' ', $this->configuration['scopes']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $configuration = $form_state->getValues();
    if ($configuration['use_well_known']) {
      $endpoints = $this->autoDiscoverEndpoints($configuration['issuer_url']);
      if ($endpoints === FALSE) {
        $form_state->setErrorByName('issuer_url', $this->t('The issuer URL @url appears to be invalid.', ['@url' => $configuration['issuer_url']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues();
    if ($configuration['use_well_known']) {
      $endpoints = $this->autoDiscoverEndpoints($configuration['issuer_url']);
      $this->setConfiguration([
        'authorization_endpoint' => $endpoints['authorization_endpoint'],
        'token_endpoint' => $endpoints['token_endpoint'],
        'userinfo_endpoint' => $endpoints['userinfo_endpoint'],
      ]);
    }
    // Don't store use_well_known in the configuration, as it is set using the
    // value of the issuer_url setting.
    $this->unsetConfigurationKeys(['use_well_known']);

    if (!empty($configuration['scopes'])) {
      $this->setConfiguration(['scopes' => explode(' ', $configuration['scopes'])]);
    }

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getClientScopes(): ?array {
    return $this->configuration['scopes'];
  }

  /**
   * Performs endpoint discovery.
   *
   * @param string $issuer_url
   *   The issuer URL.
   *
   * @return array|false
   *   Array with discovered endpoints; FALSE on failure to fetch data or the
   *   JSON response not containing the three *required* endpoints
   *   (authorization, token, userinfo).
   */
  protected function autoDiscoverEndpoints(string $issuer_url = '') {
    static $results = [];

    if (empty($issuer_url)) {
      $issuer_url = $this->configuration['issuer_url'];
    }

    if (!isset($results[$issuer_url])) {
      $results[$issuer_url] = $this->autoDiscover->fetch($issuer_url);
    }

    $result = $results[$issuer_url];
    if ($result && isset($result['authorization_endpoint']) && isset($result['token_endpoint']) && isset($result['userinfo_endpoint'])) {
      return $result;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoints() : array {
    return [
      'authorization' => $this->configuration['authorization_endpoint'],
      'token' => $this->configuration['token_endpoint'],
      'userinfo' => $this->configuration['userinfo_endpoint'],
      'end_session' => $this->configuration['end_session_endpoint'],
    ];
  }

}
