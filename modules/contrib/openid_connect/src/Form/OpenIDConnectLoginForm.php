<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenID Connect login form.
 *
 * @package Drupal\openid_connect\Form
 */
class OpenIDConnectLoginForm extends FormBase {

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
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OpenIDConnectClaims $claims, OpenIDConnectSessionInterface $session) {
    $this->entityTypeManager = $entity_type_manager;
    $this->claims = $claims;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectLoginForm {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('openid_connect.claims'),
      $container->get('openid_connect.session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openid_connect_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $clients = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['status' => TRUE]);
    foreach ($clients as $client_id => $client) {
      /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client */
      $form['openid_connect_client_' . $client_id . '_login'] = [
        '#type' => 'submit',
        '#value' => $this->t('Log in with @client_title', [
          '@client_title' => $client->label(),
        ]),
        '#name' => $client_id,
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->session->saveDestination();
    $client_name = $form_state->getTriggeringElement()['#name'];

    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client */
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];
    $plugin = $client->getPlugin();
    $scopes = $this->claims->getScopes($plugin);
    $this->session->saveOp('login');
    $response = $plugin->authorize($scopes);
    $form_state->setResponse($response);
  }

}
