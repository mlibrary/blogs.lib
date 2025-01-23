<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the user-specific OpenID Connect settings form.
 *
 * @package Drupal\openid_connect\Form
 */
class OpenIDConnectAccountsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * The OpenID Connect authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The OpenID Connect claims service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user account.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The authmap storage.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The OpenID Connect claims.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   */
  public function __construct(ConfigFactory $config_factory, EntityTypeManagerInterface $entity_type_manager, AccountProxyInterface $current_user, AuthmapInterface $authmap, OpenIDConnectClaims $claims, OpenIDConnectSessionInterface $session) {
    $this->setConfigFactory($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->authmap = $authmap;
    $this->claims = $claims;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectAccountsForm {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('externalauth.authmap'),
      $container->get('openid_connect.claims'),
      $container->get('openid_connect.session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openid_connect_accounts_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?AccountInterface $user = NULL): array {
    $form_state->set('account', $user);

    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface[] $clients */
    $clients = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['status' => TRUE]);

    $form['help'] = [
      '#prefix' => '<p class="description">',
      '#suffix' => '</p>',
    ];

    if (empty($clients)) {
      $form['help']['#markup'] = $this->t('No external account providers are available.');
      return $form;
    }
    elseif ($this->currentUser->id() == $user->id()) {
      $form['help']['#markup'] = $this->t('You can connect your account with these external providers.');
    }

    $connected_accounts = $this->authmap->getAll($user->id());

    foreach ($clients as $client) {
      $id = $client->id();
      $label = $client->label();

      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Provider: @title', ['@title' => $label]),
      ];
      $fieldset = &$form[$id];
      $connected = isset($connected_accounts['openid_connect.' . $id]);
      $fieldset['status'] = [
        '#type' => 'item',
        '#title' => $this->t('Status'),
      ];
      if ($connected) {
        $fieldset['status']['#markup'] = $this->t('Connected as %sub', [
          '%sub' => $connected_accounts['openid_connect.' . $id],
        ]);
        $fieldset['openid_connect_client_' . $id . '_disconnect'] = [
          '#type' => 'submit',
          '#value' => $this->t('Disconnect from @client_title', ['@client_title' => $label]),
          '#name' => 'disconnect__' . $id,
        ];
      }
      else {
        $fieldset['status']['#markup'] = $this->t('Not connected');
        $fieldset['openid_connect_client_' . $id . '_connect'] = [
          '#type' => 'submit',
          '#value' => $this->t('Connect with @client_title', ['@client_title' => $label]),
          '#name' => 'connect__' . $id,
          '#access' => $this->currentUser->id() == $user->id(),
        ];
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    [$op, $client_name] = explode('__', $form_state->getTriggeringElement()['#name'], 2);
    if ($op === 'connect' && !$this->access($this->currentUser)->isAllowed()) {
      $this->messenger()->addError($this->t("You cannot connect another user's account."));
      return;
    }

    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client */
    $client = $this->entityTypeManager->getStorage('openid_connect_client')->loadByProperties(['id' => $client_name])[$client_name];

    switch ($op) {
      case 'disconnect':
        $this->authmap->delete($form_state->get('account')->id(), 'openid_connect.' . $client_name);
        $this->messenger()->addMessage($this->t('Account successfully disconnected from @client.', ['@client' => $client->label()]));
        break;

      case 'connect':
        $this->session->saveDestination();

        $plugin = $client->getPlugin();
        $scopes = $this->claims->getScopes($plugin);
        $this->session->saveOp('connect', $this->currentUser->id());
        $response = $plugin->authorize($scopes);
        $form_state->setResponse($response);
        break;
    }
  }

  /**
   * Checks access for the OpenID-Connect accounts form.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user having accounts.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $user): AccessResultInterface {
    if ($this->currentUser->hasPermission('administer users')) {
      return AccessResult::allowed();
    }

    if ($this->currentUser->hasPermission('disconnect openid connected accounts')) {
      return AccessResult::allowed();
    }

    if ($this->currentUser->id() && $this->currentUser->id() === $user->id() &&
      $this->currentUser->hasPermission('manage own openid connect accounts')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
