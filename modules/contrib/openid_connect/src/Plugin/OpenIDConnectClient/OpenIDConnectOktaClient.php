<?php

namespace Drupal\openid_connect\Plugin\OpenIDConnectClient;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientBase;

/**
 * Okta OpenID Connect client.
 *
 * Implements OpenID Connect Client plugin for Okta.
 *
 * @OpenIDConnectClient(
 *   id = "okta",
 *   label = @Translation("Okta")
 * )
 */
class OpenIDConnectOktaClient extends OpenIDConnectClientBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'okta_domain' => '',
      'scopes' => ['openid', 'email'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['okta_domain'] = [
      '#title' => $this->t('Okta domain'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['okta_domain'],
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
  public function getEndpoints(): array {
    // From https://developer.okta.com/docs/reference/api/oidc and
    // https://${yourOktaDomain}/.well-known/openid-configuration
    return [
      'authorization' => 'https://' . $this->configuration['okta_domain'] . '/oauth2/v1/authorize',
      'token' => 'https://' . $this->configuration['okta_domain'] . '/oauth2/v1/token',
      'userinfo' => 'https://' . $this->configuration['okta_domain'] . '/oauth2/v1/userinfo',
      'end_session' => 'https://' . $this->configuration['okta_domain'] . '/oauth2/v1/logout',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $configuration = $form_state->getValues();
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

}
