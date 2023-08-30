<?php

namespace Drupal\openid_connect;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Main service of the OpenID Connect module.
 */
class OpenIDConnect {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The external authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface
   */
  protected $authmap;

  /**
   * The external auth.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The User entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $userStorage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The email validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The OpenID Connect logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * File system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private $fileSystem;

  /**
   * The OpenID Connect session service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectSessionInterface
   */
  protected $session;

  /**
   * The file.repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected $fileRepository;

  /**
   * Construct an instance of the OpenID Connect service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\externalauth\AuthmapInterface $authmap
   *   The external authmap service.
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   *   The external auth service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   Account proxy for the currently logged-in user.
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger channel factory instance.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\openid_connect\OpenIDConnectSessionInterface $session
   *   The OpenID Connect session service.
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *   The file.repository service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    AuthmapInterface $authmap,
    ExternalAuthInterface $external_auth,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    AccountProxyInterface $current_user,
    UserDataInterface $user_data,
    EmailValidatorInterface $email_validator,
    MessengerInterface $messenger,
    ModuleHandlerInterface $module_handler,
    LoggerChannelFactoryInterface $logger,
    FileSystemInterface $fileSystem,
    OpenIDConnectSessionInterface $session,
    FileRepositoryInterface $fileRepository
  ) {
    $this->configFactory = $config_factory;
    $this->authmap = $authmap;
    $this->externalAuth = $external_auth;
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->entityFieldManager = $entity_field_manager;
    $this->currentUser = $current_user;
    $this->userData = $user_data;
    $this->emailValidator = $email_validator;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger->get('openid_connect');
    $this->fileSystem = $fileSystem;
    $this->session = $session;
    $this->fileRepository = $fileRepository;
  }

  /**
   * Return user properties that can be ignored when mapping user profile info.
   *
   * @param array $context
   *   Optional: Array with context information, if this function is called
   *   within the context of user authorization.
   *   Defaults to an empty array.
   *
   * @return array
   *   User properties to ignore.
   */
  public function userPropertiesIgnore(array $context = []): array {
    $properties_ignore = [
      'uid',
      'uuid',
      'langcode',
      'preferred_langcode',
      'preferred_admin_langcode',
      'name',
      'pass',
      'mail',
      'status',
      'created',
      'changed',
      'access',
      'login',
      'init',
      'roles',
      'default_langcode',
    ];
    $this->moduleHandler->alter('openid_connect_user_properties_ignore', $properties_ignore, $context);

    $properties_ignore = array_unique($properties_ignore);
    return array_combine($properties_ignore, $properties_ignore);
  }

  /**
   * Fill the context array.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client
   *   The client.
   * @param array $tokens
   *   The tokens as returned by OpenIDConnectClientInterface::retrieveTokens().
   *
   * @return array|bool
   *   Context array or FALSE if an error was raised.
   */
  private function buildContext(OpenIDConnectClientEntityInterface $client, array $tokens) {
    $plugin = $client->getPlugin();
    $user_data = isset($tokens['id_token']) ? (is_string($tokens['id_token']) ? $this->parseToken($tokens['id_token']) : $tokens['id_token']) : NULL;
    $access_data = isset($tokens['access_token']) ? (is_string($tokens['access_token']) ? $this->parseToken($tokens['access_token']) : $tokens['access_token']) : NULL;
    if ($plugin->usesUserInfo()) {
      $userinfo = $plugin->retrieveUserInfo($tokens['access_token']);
    }
    elseif (is_array($user_data)) {
      $userinfo = $user_data;
    }
    elseif (is_array($access_data)) {
      $userinfo = $access_data;
    }
    else {
      $userinfo = [];
    }
    $provider = $client->id();

    $context = [
      'tokens' => $tokens,
      'plugin_id' => $provider,
      'user_data' => $user_data,
    ];
    $this->moduleHandler->alter('openid_connect_userinfo', $userinfo, $context);

    // Whether we have no usable user information.
    if ((empty($user_data) || !is_array($user_data)) && empty($userinfo)) {
      $this->logger->error('No user information provided by @provider', ['@provider' => $provider]);
      return FALSE;
    }

    if ($userinfo && empty($userinfo['email'])) {
      $this->logger->error('No e-mail address provided by @provider', ['@provider' => $provider]);
      return FALSE;
    }

    if (isset($user_data) && isset($user_data['sub'])) {
      // Set sub to FALSE, when it exists in both $user_data and $userinfo,
      // and they differ.
      $sub = (!isset($userinfo['sub']) || ($user_data['sub'] == $userinfo['sub'])) ? $user_data['sub'] : FALSE;
    }
    else {
      // No sub in $user_data, set it from $userinfo if it exists.
      $sub = (isset($userinfo['sub'])) ? $userinfo['sub'] : FALSE;
    }

    if (empty($sub)) {
      $this->logger->error('No "sub" found from @provider', ['@provider' => $provider]);
      return FALSE;
    }

    /** @var \Drupal\user\UserInterface|bool $account */
    $account = $this->externalAuth->load($sub, 'openid_connect.' . $provider);
    $context = [
      'tokens' => $tokens,
      'plugin_id' => $provider,
      'user_data' => $user_data,
      'userinfo' => $userinfo,
      'sub' => $sub,
      'account' => $account,
    ];

    $results = $this->moduleHandler
      ->invokeAll('openid_connect_pre_authorize', [$account, $context]);
    if (is_array($results)) {
      // Deny access if any module returns FALSE.
      if (in_array(FALSE, $results, TRUE)) {
        $this->logger->error('Login denied for @email via pre-authorize hook.', ['@email' => $userinfo['email']]);
        return FALSE;
      }

      // If any module returns an account, set local $account to that.
      foreach ($results as $result) {
        if ($result instanceof UserInterface) {
          $context['account'] = $result;
          break;
        }
      }
    }

    return $context;
  }

  /**
   * Complete the authorization after tokens have been retrieved.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client
   *   The client.
   * @param array $tokens
   *   The tokens as returned by OpenIDConnectClientInterface::retrieveTokens().
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @throws \Exception
   */
  public function completeAuthorization(OpenIDConnectClientEntityInterface $client, array $tokens): bool {
    if ($this->currentUser->isAuthenticated()) {
      throw new \RuntimeException('User already logged in');
    }

    $context = $this->buildContext($client, $tokens);
    if ($context === FALSE) {
      return FALSE;
    }

    $account = $context['account'];
    if ($account instanceof UserInterface) {
      // An existing account was found. Save user claims.
      if ($this->configFactory->get('openid_connect.settings')->get('always_save_userinfo')) {
        $this->saveUserinfo($account, $context + ['is_new' => FALSE]);
      }
    }
    else {
      // Check whether the e-mail address is valid.
      $email = $context['userinfo']['email'] ?? '';
      if (!$this->emailValidator->isValid($email)) {
        $this->messenger->addError($this->t('The e-mail address is not valid: @email', [
          '@email' => $email,
        ]));
        return FALSE;
      }

      // Check whether there is an e-mail address conflict.
      $accounts = $this->userStorage->loadByProperties([
        'mail' => $email,
      ]);
      if ($accounts) {
        /** @var \Drupal\user\UserInterface|bool $account */
        $account = reset($accounts);
        $connect_existing_users = $this->configFactory->get('openid_connect.settings')->get('connect_existing_users');
        if ($connect_existing_users) {
          // Connect existing user account with this sub.
          $this->externalAuth->linkExistingAccount($context['sub'], 'openid_connect.' . $client->id(), $account);
        }
        else {
          $this->messenger->addError($this->t('The e-mail address is already taken: @email', ['@email' => $email]));
          return FALSE;
        }
      }

      // Check Drupal user register settings before saving.
      $register = $this->configFactory->get('user.settings')->get('register');
      // Respect possible override from OpenID-Connect settings.
      $register_override = $this->configFactory->get('openid_connect.settings')->get('override_registration_settings');
      if ($register === UserInterface::REGISTER_ADMINISTRATORS_ONLY && $register_override) {
        $register = UserInterface::REGISTER_VISITORS;
      }

      if (empty($account)) {
        switch ($register) {
          case UserInterface::REGISTER_ADMINISTRATORS_ONLY:
            // Deny user registration.
            $this->messenger->addError($this->t('Only administrators can register new accounts.'));
            return FALSE;

          case UserInterface::REGISTER_VISITORS:
            // Create a new account if register settings is set to visitors or
            // override is active.
            $account = $this->createUser($context['sub'], $context['userinfo'], $client->id());
            break;

          case UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL:
            // Create a new account and inform the user of the pending approval.
            $account = $this->createUser($context['sub'], $context['userinfo'], $client->id(), 0);
            $this->messenger->addMessage($this->t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.'));
            break;
        }
      }

      // Store the newly created account.
      $this->saveUserinfo($account, $context + ['is_new' => TRUE]);
    }

    // Whether the user should not be logged in due to pending administrator
    // approval.
    if ($account->isBlocked()) {
      if (empty($context['is_new'])) {
        $this->messenger->addError($this->t('The username %name has not been activated or is blocked.', [
          '%name' => $account->getAccountName(),
        ]));
      }
      return FALSE;
    }

    $this->externalAuth->userLoginFinalize($account, $context['sub'], 'openid_connect.' . $client->id());
    if (isset($tokens['id_token'])) {
      $this->session->saveIdToken($tokens['id_token']);
    }
    if (isset($tokens['access_token'])) {
      $this->session->saveAccessToken($tokens['access_token']);
    }

    $this->moduleHandler
      ->invokeAll('openid_connect_post_authorize', [$account, $context]);

    return TRUE;
  }

  /**
   * Connect the current user's account to an external provider.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $client
   *   The client.
   * @param array $tokens
   *   The tokens as returned by OpenIDConnectClientInterface::retrieveTokens().
   *
   * @return bool
   *   TRUE on success, FALSE on failure.
   *
   * @throws \Exception
   */
  public function connectCurrentUser(OpenIDConnectClientEntityInterface $client, array $tokens): bool {
    if (!$this->currentUser->isAuthenticated()) {
      throw new \RuntimeException('User not logged in');
    }

    $context = $this->buildContext($client, $tokens);
    if ($context === FALSE) {
      return FALSE;
    }

    $account = $context['account'];
    if (($account instanceof UserInterface) && $account->id() !== $this->currentUser->id()) {
      $this->messenger->addError($this->t('Another user is already connected to this @provider account.', ['@provider' => $client->id()]));
      return FALSE;
    }

    if (!($account instanceof UserInterface)) {
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->userStorage->load($this->currentUser->id());
      if ($account) {
        $this->externalAuth->linkExistingAccount($context['sub'], 'openid_connect.' . $client->id(), $account);
      }
    }

    if ($account) {
      $always_save_userinfo = $this->configFactory->get('openid_connect.settings')->get('always_save_userinfo');
      if ($always_save_userinfo) {
        $this->saveUserinfo($account, $context);
      }

      $this->moduleHandler->invokeAll('openid_connect_post_authorize',
        [$account, $context]);

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Find whether a user is allowed to change the own password.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   Optional: Account to check the access for.
   *   Defaults to the currently logged-in user.
   *
   * @return bool
   *   TRUE if access is granted, FALSE otherwise.
   */
  public function hasSetPasswordAccess(AccountInterface $account = NULL): bool {
    if (empty($account)) {
      $account = $this->currentUser;
    }

    if ($account->hasPermission('openid connect set own password')) {
      return TRUE;
    }

    $connected_accounts = $this->authmap->getAll($account->id());

    return empty($connected_accounts);
  }

  /**
   * Create a user indicating sub-id and login provider.
   *
   * @param string $sub
   *   The subject identifier.
   * @param array $userinfo
   *   The user claims, containing at least 'email'.
   * @param string $client_name
   *   The machine name of the client.
   * @param int $status
   *   The initial user status.
   *
   * @return \Drupal\user\UserInterface|null
   *   The user object or null on failure.
   */
  public function createUser(string $sub, array $userinfo, string $client_name, int $status = 1): ?UserInterface {
    $account_data = [
      'name' => $this->generateUsername($sub, $userinfo, $client_name),
      'mail' => $userinfo['email'],
      'init' => $userinfo['email'],
      'status' => $status,
    ];

    return $this->externalAuth->register($sub, 'openid_connect.' . $client_name, $account_data);
  }

  /**
   * Generate a username for a new account.
   *
   * @param string $sub
   *   The subject identifier.
   * @param array $userinfo
   *   The user claims.
   * @param string $client_name
   *   The client identifier.
   *
   * @return string
   *   A unique username.
   */
  public function generateUsername(string $sub, array $userinfo, string $client_name): string {
    $name = 'oidc_' . $client_name . '_' . md5($sub);
    $candidates = ['preferred_username', 'name'];
    foreach ($candidates as $candidate) {
      if (!empty($userinfo[$candidate])) {
        $name = trim($userinfo[$candidate]);
        break;
      }
    }

    // Ensure there are no duplicates.
    for ($original = $name, $i = 1; $this->usernameExists($name); $i++) {
      $name = $original . '_' . $i;
    }

    return $name;
  }

  /**
   * Check if a user name already exists.
   *
   * @param string $name
   *   A name to test.
   *
   * @return bool
   *   TRUE if a user exists with the given name, FALSE otherwise.
   */
  public function usernameExists(string $name): bool {
    $users = $this->userStorage->loadByProperties([
      'name' => $name,
    ]);

    return (bool) $users;
  }

  /**
   * Save user profile information into a user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   An user account object.
   * @param array $context
   *   An associative array with context information:
   *   - tokens:         An array of tokens.
   *   - user_data:      An array of user and session data.
   *   - userinfo:       An array of user information.
   *   - plugin_id:      The plugin identifier.
   *   - sub:            The remote user identifier.
   *
   * @return bool
   *   Whether the user info was successfully saved.
   */
  public function saveUserinfo(UserInterface $account, array $context): bool {
    $userinfo = $context['userinfo'];
    $properties = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $properties_skip = $this->userPropertiesIgnore($context);
    foreach ($properties as $property_name => $property) {
      if (isset($properties_skip[$property_name])) {
        continue;
      }

      $userinfo_mappings = $this->configFactory->get('openid_connect.settings')->get('userinfo_mappings');
      if (isset($userinfo_mappings[$property_name])) {
        $claim = $userinfo_mappings[$property_name];

        if ($claim && isset($userinfo[$claim])) {
          $claim_value = $userinfo[$claim];
          $property_type = $property->getType();

          $claim_context = $context + [
            'claim' => $claim,
            'property_name' => $property_name,
            'property_type' => $property_type,
            'userinfo_mappings' => $userinfo_mappings,
          ];
          $this->moduleHandler->alter('openid_connect_userinfo_claim', $claim_value, $claim_context);

          // Set the user property, while ignoring exceptions from invalid
          // values.
          try {
            switch ($property_type) {
              case 'string':
              case 'string_long':
              case 'list_string':
              case 'datetime':
                $account->set($property_name, $claim_value);
                break;

              case 'boolean':
                $account->set($property_name, !empty($claim_value));
                break;

              case 'entity_reference':
                $account->set($property_name, ['target_id' => $claim_value]);
                break;

              case 'image':
                // Create file object from remote URL.
                $basename = explode('?', $this->fileSystem->basename($claim_value))[0];
                $data = file_get_contents($claim_value);

                $file = $this->fileRepository->writeData(
                  $data,
                  "public://user-picture-{$account->id()}-{$basename}",
                  FileSystemInterface::EXISTS_RENAME
                );

                // Cleanup the old file.
                if ($file) {
                  $old_file = $account->$property_name->entity;
                  if ($old_file) {
                    $old_file->delete();
                  }
                }

                $account->set($property_name, ['target_id' => $file->id()]);
                break;

              default:
                $this->logger->error('Could not save user info, property type not implemented: %property_type',
                  ['%property_type' => $property_type]
                );
                break;

            }
          }
          // Catch the error if the field does not exist.
          catch (\InvalidArgumentException $e) {
            $this->logger->error($e->getMessage());
          }
        }
      }
    }

    // Map groups to Drupal roles.
    if (isset($userinfo['groups'])) {
      $role_mappings = $this->configFactory->get('openid_connect.settings')->get('role_mappings');
      foreach ($role_mappings as $role => $mappings) {
        if (!empty(array_intersect($mappings, $userinfo['groups']))) {
          // User has a mapped role. Add it to their account.
          $account->addRole($role);
        }
        else {
          // User doesn't have a mapped role. Remove it from their account.
          $account->removeRole($role);
        }
      }
    }

    // Save the display name additionally in the user account 'data', for
    // use in openid_connect_username_alter().
    if (isset($userinfo['name'])) {
      $this->userData->set('openid_connect', $account->id(), 'oidc_name', $userinfo['name']);
    }

    // Allow other modules to add additional user information.
    $this->moduleHandler->invokeAll('openid_connect_userinfo_save',
      [$account, $context]
    );

    try {
      $account->save();
      return TRUE;
    }
    catch (EntityStorageException $e) {
      return FALSE;
    }
  }

  /**
   * Parse JWT token.
   *
   * @param string $token
   *   The encoded ID token containing the user data.
   *
   * @return array|string
   *   The parsed JWT token or the original string.
   */
  protected function parseToken(string $token) {
    $parts = explode('.', $token, 3);
    if (count($parts) === 3) {
      $decoded = Json::decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])));
      if (is_array($decoded)) {
        return $decoded;
      }
    }
    return $token;
  }

}
