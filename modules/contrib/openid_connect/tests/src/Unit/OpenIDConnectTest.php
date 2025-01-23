<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Unit;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\TestTools\Random;
use Drupal\user\Entity\User;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use Psr\Log\InvalidArgumentException;

/**
 * Provides tests for the OpenID Connect module.
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnect
 * @group openid_connect
 */
class OpenIDConnectTest extends UnitTestCase {

  /**
   * Mock of the config factory.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock of the external authmap service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $authmap;

  /**
   * Mock of the externalAuth service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $externalAuth;

  /**
   * Mock of the entity_type.manager service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock of the entity field manager service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * Mock of the account_proxy service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * Mock of the user data interface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $userData;

  /**
   * Mock of the email validator.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $emailValidator;

  /**
   * Mock of the messenger service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * Mock of the module handler service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * Mock of the logger interface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * The OpenIDConnect class being tested.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIdConnect;

  /**
   * Mock of the userStorageInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $userStorage;

  /**
   * Mock of the open id connect logger.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $oidcLogger;

  /**
   * Mock of the FileSystemInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileSystem;

  /**
   * Mock of the OpenIDConnectSessionInterface.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $session;

  /**
   * Mock of the file.repository service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $oldFileMock = $this->createMock(File::class);
    $oldFileMock->expects($this->any())
      ->method('id')
      ->willReturn(123);

    require_once 'UserPasswordFixture.php';

    // Mock the config_factory service.
    $this->configFactory = $this
      ->createMock(ConfigFactoryInterface::class);

    // Mock the external authMap service.
    $this->authmap = $this
      ->createMock(AuthmapInterface::class);

    // Mock the externalAuth connect service.
    $this->externalAuth = $this
      ->createMock(ExternalAuthInterface::class);

    $this->userStorage = $this
      ->createMock(EntityStorageInterface::class);

    // Mock the entity type manager service.
    $this->entityTypeManager = $this
      ->createMock(EntityTypeManagerInterface::class);

    $this->entityTypeManager->expects($this->atLeastOnce())
      ->method('getStorage')
      ->with('user')
      ->willReturn($this->userStorage);

    $this->entityFieldManager = $this
      ->createMock(EntityFieldManagerInterface::class);

    $this->currentUser = $this
      ->createMock(AccountProxyInterface::class);

    $this->userData = $this
      ->createMock(UserDataInterface::class);

    $this->emailValidator = $this->createMock(EmailValidator::class);

    $this->messenger = $this
      ->createMock(MessengerInterface::class);

    $this->moduleHandler = $this
      ->createMock(ModuleHandler::class);

    $this->logger = $this
      ->createMock(LoggerChannelFactoryInterface::class);

    $this->oidcLogger = $this
      ->createMock(LoggerChannelInterface::class);

    $this->logger->expects($this->atLeastOnce())
      ->method('get')
      ->with('openid_connect')
      ->willReturn($this->oidcLogger);

    $this->fileSystem = $this
      ->createMock(FileSystemInterface::class);

    $this->session = $this
      ->createMock(OpenIDConnectSessionInterface::class);

    $this->fileRepository = $this->createMock(FileRepositoryInterface::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('entity_type.repository', $this->createMock(EntityTypeRepositoryInterface::class));
    $container->set('entity_type.manager', $this->createMock(EntityTypeManagerInterface::class));
    \Drupal::setContainer($container);

    $this->openIdConnect = new OpenIDConnect(
      $this->configFactory,
      $this->authmap,
      $this->externalAuth,
      $this->entityTypeManager,
      $this->entityFieldManager,
      $this->currentUser,
      $this->userData,
      $this->emailValidator,
      $this->messenger,
      $this->moduleHandler,
      $this->logger,
      $this->fileSystem,
      $this->session,
      $this->fileRepository
    );
  }

  /**
   * Test for the userPropertiesIgnore method.
   */
  public function testUserPropertiesIgnore(): void {
    $defaultPropertiesIgnore = [
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
    $expectedResults = array_combine($defaultPropertiesIgnore, $defaultPropertiesIgnore);

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with(
        'openid_connect_user_properties_ignore',
        $defaultPropertiesIgnore,
        []
      );

    $actualPropertiesIgnored = $this->openIdConnect->userPropertiesIgnore([]);

    $this->assertEquals($expectedResults, $actualPropertiesIgnored);
  }

  /**
   * Test for the hasSetPassword method.
   *
   * @param bool $hasPermission
   *   Whether the account should have the correct permission
   *   to change their own password.
   * @param array $connectedAccounts
   *   The connected accounts array from the authMap method.
   * @param bool $expectedResult
   *   The result expected.
   *
   * @dataProvider dataProviderForHasSetPasswordAccess
   */
  public function testHasSetPasswordAccess(
    bool $hasPermission,
    array $connectedAccounts,
    bool $expectedResult,
  ): void {
    $this->currentUser->expects($this->once())
      ->method('hasPermission')
      ->with('openid connect set own password')
      ->willReturn($hasPermission);

    if (!$hasPermission) {
      $this->currentUser->expects($this->once())
        ->method('id')
        ->willReturn(3);

      $this->authmap->expects($this->once())
        ->method('getAll')
        ->willReturn($connectedAccounts);
    }

    $actualResult = $this->openIdConnect->hasSetPasswordAccess($this->currentUser);

    $this->assertEquals($expectedResult, $actualResult);
  }

  /**
   * Data provider for the testHasSetPasswordAccess method.
   *
   * @return array|array[]
   *   Data provider parameters for the testHasSetPassword() method.
   */
  public static function dataProviderForHasSetPasswordAccess(): array {
    $connectedAccounts = [
      Random::machineName() => 'sub',
    ];

    return [
      [FALSE, [], TRUE],
      [TRUE, [], TRUE],
      [TRUE, [], TRUE],
      [FALSE, [], TRUE],
      [FALSE, $connectedAccounts, FALSE],
      [TRUE, $connectedAccounts, TRUE],
      [TRUE, $connectedAccounts, TRUE],
      [FALSE, $connectedAccounts, FALSE],
    ];
  }

  /**
   * Test for the createUser method.
   *
   * @param string $sub
   *   The sub to use.
   * @param array $userinfo
   *   The userinfo array containing the email key.
   * @param string $client_name
   *   The client name for the user.
   * @param int $status
   *   The user status.
   * @param bool $duplicate
   *   Whether to test a duplicate username.
   *
   * @dataProvider dataProviderForCreateUser
   */
  public function testCreateUser(
    string $sub,
    array $userinfo,
    string $client_name,
    int $status,
    bool $duplicate,
  ): void {
    // Mock the expected username.
    $expectedUserName = 'oidc_' . $client_name . '_' . md5($sub);

    // If the preferred username is defined, use it instead.
    if (array_key_exists('preferred_username', $userinfo)) {
      $expectedUserName = trim($userinfo['preferred_username']);
    }

    // If the name key exists, use it.
    if (array_key_exists('name', $userinfo)) {
      $expectedUserName = trim($userinfo['name']);
    }

    $expectedAccountArray = [
      'name' => ($duplicate ? "{$expectedUserName}_1" : $expectedUserName),
      'mail' => $userinfo['email'],
      'init' => $userinfo['email'],
      'status' => $status,
    ];

    // Mock the user account to be created.
    $account = $this
      ->createMock(UserInterface::class);

    $this->externalAuth->expects($this->once())
      ->method('register')
      ->with($sub, 'openid_connect.' . $client_name, $expectedAccountArray)
      ->willReturn($account);

    if ($duplicate) {
      $this->userStorage->expects($this->exactly(2))
        ->method('loadByProperties')
        ->willReturnMap([
          [['name' => $expectedUserName], [$account]],
          [['name' => "{$expectedUserName}_1"], []],
        ]);
    }
    else {
      $this->userStorage->expects($this->once())
        ->method('loadByProperties')
        ->with(['name' => $expectedUserName])
        ->willReturn([]);
    }

    $actualResult = $this->openIdConnect
      ->createUser($sub, $userinfo, $client_name, $status);

    $this->assertInstanceOf('\Drupal\user\UserInterface', $actualResult);
  }

  /**
   * Data provider for the testCreateUser method.
   *
   * @return array|array[]
   *   The parameters to pass to testCreateUser().
   */
  public static function dataProviderForCreateUser(): array {
    return [
      [Random::machineName(), ['email' => 'test@123.com'], '', 0, FALSE],
      [Random::machineName(),
        [
          'email' => 'test@test123.com',
          'name' => Random::machineName(),
        ], Random::machineName(), 1, FALSE,
      ],
      [Random::machineName(),
        [
          'email' => 'test@test456.com',
          'preferred_username' => Random::machineName(),
        ], Random::machineName(), 1, TRUE,
      ],
    ];
  }

  /**
   * Test coverage for the completeAuthorization() method.
   *
   * @param bool $authenticated
   *   Should the user be authenticated.
   * @param string $destination
   *   Destination string.
   * @param array $tokens
   *   Tokens array.
   * @param array|string $userData
   *   The user data.
   * @param array $userInfo
   *   The user info array.
   * @param bool $preAuthorize
   *   Whether to pre-authorize or not.
   * @param bool $accountExists
   *   Does the account already exist.
   *
   * @dataProvider dataProviderForCompleteAuthorization
   * @runInSeparateProcess
   */
  public function testCompleteAuthorization(
    bool $authenticated,
    string $destination,
    array $tokens,
    $userData,
    array $userInfo,
    bool $preAuthorize,
    bool $accountExists,
  ): void {
    $clientId = $this->randomMachineName();

    $this->currentUser->expects($this->once())
      ->method('isAuthenticated')
      ->willReturn($authenticated);

    $client = $this->createMock(OpenIDConnectClientInterface::class);
    $clientEntity = $this->createMock(OpenIDConnectClientEntityInterface::class);

    if ($authenticated) {
      $this->expectException('RuntimeException');
    }
    else {
      $client->expects($this->once())
        ->method('usesUserInfo')
        ->willReturn(TRUE);

      $client->expects($this->once())
        ->method('retrieveUserInfo')
        ->with($tokens['access_token'])
        ->willReturn($userInfo);

      $clientEntity->expects($this->any())
        ->method('getPlugin')
        ->willReturn($client);

      $clientEntity->expects($this->any())
        ->method('id')
        ->willReturn($clientId);

      if ($accountExists) {
        if (!$preAuthorize) {
          $moduleHandlerResults = [1, 2, FALSE];
        }
        else {
          $returnedAccount = $this
            ->createMock(UserInterface::class);

          if (!empty($userInfo['blocked'])) {
            $returnedAccount->expects($this->once())
              ->method('isBlocked')
              ->willReturn(TRUE);

            $this->messenger->expects($this->once())
              ->method('addError');
          }

          $moduleHandlerResults = [$returnedAccount];
        }

        $this->moduleHandler->expects($this->once())
          ->method('alter')
          ->with(
            'openid_connect_userinfo',
            $userInfo,
            [
              'tokens' => $tokens,
              'plugin_id' => $clientId,
              'user_data' => $userData,
            ]
          );

        if (empty($userData) && empty($userInfo)) {
          $this->oidcLogger->expects($this->once())
            ->method('error')
            ->with(
              'No user information provided by @provider',
              ['@provider' => $clientId]
            );
        }

        if (!empty($userInfo) && empty($userInfo['email'])) {
          $this->oidcLogger->expects($this->once())
            ->method('error')
            ->with(
              'No email address provided by @provider',
              ['@provider' => $clientId]
            );
        }

        if (!empty($userInfo['sub'])) {
          $account = $this->createMock(UserInterface::class);
          $account->method('id')->willReturn(1234);
          $account->method('isNew')->willReturn(FALSE);

          $this->externalAuth->expects($this->once())
            ->method('load')
            ->willReturn($account);

          $this->moduleHandler->expects($this->any())
            ->method('invokeAll')
            ->willReturnCallback(function ($parameter) use ($moduleHandlerResults) {
              return match ($parameter) {
                'openid_connect_pre_authorize' => $moduleHandlerResults,
                'openid_connect_userinfo_save' => TRUE,
                'openid_connect_post_authorize' => TRUE,
              };
            });

          if ($preAuthorize) {
            $this->entityFieldManager->expects($this->once())
              ->method('getFieldDefinitions')
              ->with('user', 'user')
              ->willReturn(['mail' => 'mail']);

            $immutableConfig = $this
              ->createMock(ImmutableConfig::class);

            $immutableConfig->expects($this->atLeastOnce())
              ->method('get')
              ->willReturnMap([
                ['always_save_userinfo', TRUE],
                ['userinfo_mappings', ['mail', 'name']],
              ]);

            $this->configFactory->expects($this->atLeastOnce())
              ->method('get')
              ->with('openid_connect.settings')
              ->willReturn($immutableConfig);
          }
        }
      }
      else {
        $account = FALSE;

        $this->externalAuth->expects($this->once())
          ->method('load')
          ->willReturn($account);

        $this->moduleHandler->expects($this->any())
          ->method('invokeAll')
          ->with('openid_connect_pre_authorize')
          ->willReturn([]);

        if ($userInfo['email'] === 'invalid') {
          $this->messenger->expects($this->once())
            ->method('addError');
        }
        else {
          if ($userInfo['email'] === 'duplicate@valid.com') {
            $account = $this
              ->createMock(UserInterface::class);

            $this->userStorage->expects($this->once())
              ->method('loadByProperties')
              ->with(['mail' => $userInfo['email']])
              ->willReturn([$account]);

            $this->emailValidator->expects($this->once())
              ->method('isValid')
              ->with($userInfo['email'])
              ->willReturn(TRUE);

            $immutableConfig = $this
              ->createMock(ImmutableConfig::class);

            $immutableConfig->expects($this->once())
              ->method('get')
              ->with('connect_existing_users')
              ->willReturn(FALSE);

            $this->configFactory->expects($this->once())
              ->method('get')
              ->with('openid_connect.settings')
              ->willReturn($immutableConfig);

            $this->messenger->expects($this->once())
              ->method('addError');
          }
          elseif ($userInfo['email'] === 'connect@valid.com') {
            $this->entityFieldManager->expects($this->any())
              ->method('getFieldDefinitions')
              ->with('user', 'user')
              ->willReturn(['mail' => 'mail']);

            $context = [
              'tokens' => $tokens,
              'plugin_id' => $clientId,
              'user_data' => $userData,
            ];

            $this->moduleHandler->expects($this->once())
              ->method('alter')
              ->with(
                'openid_connect_userinfo',
                $userInfo,
                $context
              );

            if (isset($userInfo['newAccount']) && $userInfo['newAccount']) {
              $account = FALSE;
            }
            else {
              $account = $this
                ->createMock(UserInterface::class);

              if (isset($userInfo['blocked']) && $userInfo['blocked']) {
                $account->expects($this->once())
                  ->method('isBlocked')
                  ->willReturn(TRUE);
              }
            }

            if (isset($userInfo['newAccount']) && $userInfo['newAccount']) {
              $this->userStorage->expects($this->once())
                ->method('loadByProperties')
                ->with(['mail' => $userInfo['email']])
                ->willReturn(FALSE);
            }
            else {
              $this->userStorage->expects($this->once())
                ->method('loadByProperties')
                ->with(['mail' => $userInfo['email']])
                ->willReturn([$account]);
            }

            if (isset($userInfo['register'])) {
              switch ($userInfo['register']) {
                case 'admin_only':
                  if (empty($userInfo['registerOverride'])) {
                    $this->messenger->expects($this->once())
                      ->method('addError');
                  }
                  break;

                case 'visitors_admin_approval':
                  $this->messenger->expects($this->once())
                    ->method('addMessage');
                  break;

              }
            }

            $immutableConfig = $this->createMock(ImmutableConfig::class);

            $ret = !(empty($userInfo['registerOverride']) && isset($userInfo['newAccount']) && $userInfo['newAccount']);

            $immutableConfig->expects($this->any())
              ->method('get')
              ->willReturnMap([
                ['connect_existing_users', $ret],
                ['override_registration_settings', $ret],
                ['userinfo_mappings', ['mail' => 'mail']],
              ]);

            $this->configFactory->expects($this->any())
              ->method('get')
              ->with('openid_connect.settings')
              ->willReturn($immutableConfig);

            $userImmutableConfig = $this->createMock(ImmutableConfig::class);

            $userImmutableConfig->expects($this->any())
              ->method('get')
              ->with('register')
              ->willReturn($userInfo['register'] ?? FALSE);

            $this->configFactory->expects($this->any())
              ->method('get')
              ->with('user.settings')
              ->willReturn($userImmutableConfig);
          }
        }
      }
    }

    $oidcMock = $this->getMockBuilder('\Drupal\openid_connect\OpenIDConnect')
      ->setConstructorArgs([
        $this->configFactory,
        $this->authmap,
        $this->externalAuth,
        $this->entityTypeManager,
        $this->entityFieldManager,
        $this->currentUser,
        $this->userData,
        $this->emailValidator,
        $this->messenger,
        $this->moduleHandler,
        $this->logger,
        $this->fileSystem,
        $this->session,
        $this->fileRepository,
      ])
      ->onlyMethods([
        'userPropertiesIgnore',
        'createUser',
      ])
      ->getMock();

    $oidcMock->method('userPropertiesIgnore')
      ->willReturn(['uid' => 'uid', 'name' => 'name']);

    $oidcMock->method('createUser')
      ->willReturn($this->createMock(UserInterface::class));

    $authorization = $oidcMock->completeAuthorization($clientEntity, $tokens);

    if (empty($userData) && empty($userInfo)) {
      $this->assertEquals(FALSE, $authorization);
    }

    if (!empty($userInfo) && empty($userInfo['email'])) {
      $this->assertEquals(FALSE, $authorization);
    }
  }

  /**
   * Data provider for the testCompleteAuthorization() method.
   *
   * @return array|array[]
   *   Test parameters to pass to testCompleteAuthorization().
   */
  public static function dataProviderForCompleteAuthorization(): array {
    $sub = Random::machineName();
    $user_data = ['sub' => $sub];
    $id_token = implode('.', [
      Random::machineName(),
      base64_encode(Json::encode($user_data)),
      Random::machineName(),
    ]);
    $tokens = [
      "id_token" => $id_token,
      "access_token" => Random::machineName(),
    ];

    return [
      [TRUE, '', [], [], [], FALSE, TRUE],
      [FALSE, '', $tokens, $user_data, [], FALSE, TRUE],
      [FALSE, '', $tokens, $user_data, ['email' => ''], FALSE, TRUE],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'test@test.com',
          'sub' => $sub,
        ], FALSE, TRUE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'test@test.com',
          'sub' => $sub,
        ], TRUE, TRUE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'invalid',
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'duplicate@valid.com',
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      // @todo Fix these test cases. At the moment, they throw an exception
      // due to an unknown config get.
      /*[FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'blocked' => TRUE,
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'blocked' => TRUE,
          'sub' => 'TESTING',
        ], TRUE, TRUE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'newAccount' => TRUE,
          'register' => 'admin_only',
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'newAccount' => TRUE,
          'register' => 'visitors',
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'newAccount' => TRUE,
          'register' => 'visitors_admin_approval',
          'sub' => $sub,
        ], TRUE, FALSE,
      ],
      [FALSE, '', $tokens, $user_data,
        [
          'email' => 'connect@valid.com',
          'newAccount' => TRUE,
          'register' => 'admin_only',
          'registerOverride' => TRUE,
          'sub' => $sub,
        ], TRUE, FALSE,
      ],*/
    ];
  }

  /**
   * Test the connectCurrentUser method.
   *
   * @param bool $authenticated
   *   Whether the user is authenticated.
   * @param array $tokens
   *   The tokens to return.
   * @param array $userData
   *   The user data array.
   * @param array $userInfo
   *   The user info array.
   * @param bool $expectedResult
   *   The expected result of the method.
   *
   * @dataProvider dataProviderForConnectCurrentUser
   */
  public function testConnectCurrentUser(
    bool $authenticated,
    array $tokens,
    array $userData,
    array $userInfo,
    bool $expectedResult,
  ): void {
    $pluginId = $this->randomMachineName();
    $clientId = $this->randomMachineName();

    $client = $this->createMock(OpenIDConnectClientInterface::class);
    $clientEntity = $this->createMock(OpenIDConnectClientEntityInterface::class);

    $this->currentUser->expects($this->once())
      ->method('isAuthenticated')
      ->willReturn($authenticated);

    if (!$authenticated) {
      $this->expectException('RuntimeException');
    }
    else {
      $client->expects($this->once())
        ->method('usesUserInfo')
        ->willReturn(TRUE);

      $client->expects($this->once())
        ->method('retrieveUserInfo')
        ->with($tokens['access_token'])
        ->willReturn($userInfo);

      $clientEntity->expects($this->any())
        ->method('getPlugin')
        ->willReturn($client);

      $clientEntity->expects($this->any())
        ->method('getPluginId')
        ->willReturn($pluginId);

      $clientEntity->expects($this->any())
        ->method('id')
        ->willReturn($clientId);

      if (empty($userInfo) && empty($userData)) {
        $this->oidcLogger->expects($this->once())
          ->method('error')
          ->with(
            'No user information provided by @provider',
            ['@provider' => $clientId]
          );
      }

      if (isset($userInfo['email']) && empty($userInfo['email'])) {
        $this->oidcLogger->expects($this->once())
          ->method('error')
          ->with(
            'No email address provided by @provider',
            ['@provider' => $clientId]
          );
      }

      if (isset($userData['sub']) && $userData['sub'] === 'invalid') {
        $account = $this->createMock(User::class);

        $this->externalAuth->expects($this->once())
          ->method('load')
          ->willReturn($account);

        $this->externalAuth->expects($this->never())
          ->method('linkExistingAccount');

        $this->moduleHandler->expects($this->once())
          ->method('invokeAll')
          ->with('openid_connect_pre_authorize')
          ->willReturn([FALSE]);
      }

      if (isset($userData['sub']) && $userData['sub'] === 'different_account') {
        $accountId = 8675309;
        $userId = 3456;

        $this->currentUser->expects($this->once())
          ->method('id')
          ->willReturn($userId);

        $account = $this->createMock(User::class);

        $account->expects($this->once())
          ->method('id')
          ->willReturn($accountId);

        $this->externalAuth->expects($this->once())
          ->method('load')
          ->willReturn($account);

        $this->externalAuth->expects($this->never())
          ->method('linkExistingAccount');

        $this->moduleHandler->expects($this->once())
          ->method('invokeAll')
          ->with('openid_connect_pre_authorize')
          ->willReturn([$account]);

        $this->messenger->expects($this->once())
          ->method('addError');
      }

      if (isset($userData['sub']) && $expectedResult) {
        $accountId = 8675309;

        $this->currentUser->expects($this->once())
          ->method('id')
          ->willReturn($accountId);

        $account = $this->createMock(User::class);

        $this->userStorage->expects($this->once())
          ->method('load')
          ->with($accountId)
          ->willReturn($account);

        $this->externalAuth->expects($this->once())
          ->method('load')
          ->willReturn(FALSE);

        $this->externalAuth->expects($this->once())
          ->method('linkExistingAccount')
          ->with(
            $userData['sub'],
            'openid_connect.' . $clientId,
            $account
          );

        $mappings = [
          'mail' => 'mail',
          'name' => 'name',
        ];

        if ($userData['always_save'] === TRUE) {
          $fieldDefinitions = [];
          foreach ($userInfo as $key => $value) {
            $mappings[$key] = $key;

            switch ($key) {
              case 'email':
                $returnType = 'string';
                break;

              case 'field_string':
                $account->expects($this->any())
                  ->method('set');

                $returnType = 'string';
                break;

              case 'field_string_long':
                $account->expects($this->any())
                  ->method('set');
                $returnType = 'string_long';
                break;

              case 'field_datetime':
                $account->expects($this->any())
                  ->method('set');
                $returnType = 'datetime';
                break;

              case 'field_image':
                $this->fileSystem->expects($this->any())
                  ->method('basename')
                  ->with($value)
                  ->willReturn('test-file');
                $account->expects($this->any())
                  ->method('set');

                $returnType = 'image';

                $mockFile = $this->createMock(File::class);
                $mockFile->expects($this->once())
                  ->method('delete');

                $fieldItem = $this
                  ->createMock(FieldItemListInterface::class);
                $fieldItem->expects($this->once())
                  ->method('__get')
                  ->with('entity')
                  ->willReturn($mockFile);

                $account->expects($this->once())
                  ->method('__get')
                  ->willReturn($fieldItem);
                break;

              case 'field_invalid':
                $account->expects($this->any())
                  ->method('set');

                $this->oidcLogger->expects($this->once())
                  ->method('error')
                  ->with(
                    'Could not save user info, property type not implemented: %property_type',
                    ['%property_type' => $key]
                  );
                $returnType = $key;
                break;

              case 'field_image_exception':
                $exception = $this
                  ->createMock(InvalidArgumentException::class);

                $account->expects($this->any())
                  ->method('set')
                  ->willThrowException($exception);

                $returnType = 'string';
                break;

              default:
                $returnType = $key;
                break;
            }

            $mock = $this->createMock(FieldDefinitionInterface::class);

            $mock->expects($this->any())
              ->method('getType')
              ->willReturn($returnType);

            $fieldDefinitions[$key] = $mock;
          }

          $this->entityFieldManager->expects($this->once())
            ->method('getFieldDefinitions')
            ->with('user', 'user')
            ->willReturn($fieldDefinitions);

          $this->moduleHandler->expects($this->exactly(3))
            ->method('invokeAll')
            ->willReturnMap([
              ['openid_connect_pre_authorize', []],
              ['openid_connect_userinfo_save', TRUE],
              ['openid_connect_post_authorize', TRUE],
            ]);
        }
        else {
          $this->moduleHandler->expects($this->exactly(2))
            ->method('invokeAll')
            ->willReturnMap([
              ['openid_connect_pre_authorize', []],
              ['openid_connect_post_authorize', TRUE],
            ]);
        }

        $immutableConfig = $this->createMock(ImmutableConfig::class);

        $immutableConfig->expects($this->atLeastOnce())
          ->method('get')
          ->willReturnMap([
            ['always_save_userinfo', $userData['always_save']],
            ['userinfo_mappings', $mappings],
          ]);

        $this->configFactory->expects($this->atLeastOnce())
          ->method('get')
          ->with('openid_connect.settings')
          ->willReturn($immutableConfig);
      }
    }

    $result = $this->openIdConnect->connectCurrentUser($clientEntity, $tokens);

    $this->assertEquals($expectedResult, $result);
  }

  /**
   * Data provider for the testConnectCurrentUser method.
   *
   * @return array|array[]
   *   Array of parameters to pass to testConnectCurrentUser().
   */
  public static function dataProviderForConnectCurrentUser(): array {
    $sub = Random::machineName();
    $user_data = ['sub' => $sub];
    $id_token = implode('.', [
      Random::machineName(),
      base64_encode(Json::encode($user_data)),
      Random::machineName(),
    ]);
    $tokens = [
      "id_token" => $id_token,
      "access_token" => Random::machineName(),
    ];

    return [
      [FALSE, [], [], [], FALSE],
      [TRUE,
        [
          'id_token' => Random::machineName(),
          'access_token' => Random::machineName(),
        ], [], [], FALSE,
      ],
      [TRUE, $tokens, [], ['email' => FALSE], FALSE],
      [TRUE, $tokens, ['sub' => 'invalid'],
        ['email' => 'valid@email.com'], FALSE,
      ],
      [TRUE, $tokens, ['sub' => 'different_account'],
        ['email' => 'valid@email.com'], FALSE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => FALSE],
        ['email' => 'valid@email.com'], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        ['email' => 'valid@email.com', 'name' => Random::machineName()], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        [
          'name' => Random::machineName(),
          'field_string' => 'This is a string',
          'email' => 'valid@email.com',
        ], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        [
          'field_string_long' => 'This is long text.',
          'email' => 'valid@email.com',
          'name' => Random::machineName(),
        ], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        [
          'field_datetime' => '2020-05-20',
          'email' => 'valid@email.com',
          'name' => Random::machineName(),
        ], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        [
          'name' => Random::machineName(),
          'field_image' => realpath(__DIR__) . '/image.png',
          'email' => 'valid@email.com',
        ], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        [
          'name' => Random::machineName(),
          'field_invalid' => 'does_not_exist',
          'email' => 'valid@email.com',
        ], TRUE,
      ],
      [TRUE, $tokens, ['sub' => $sub, 'always_save' => TRUE],
        [
          'name' => Random::machineName(),
          'field_image_exception' => new \stdClass(),
          'email' => 'valid@email.com',
        ], TRUE,
      ],
    ];
  }

  /**
   * Test the saveUserinfo method.
   *
   * @param array $userinfo
   *   The mocked userinfo array.
   * @param array $mappings
   *   The configured role mappings.
   * @param array $add
   *   The roles expected to be added.
   * @param array $remove
   *   The roles expected to be removed.
   *
   * @dataProvider dataProviderForTestRoleMappings
   */
  public function testRoleMappings(array $userinfo, array $mappings, array $add, array $remove): void {
    $account = $this->createMock(UserInterface::class);
    $this->entityFieldManager->expects($this->once())
      ->method('getFieldDefinitions')
      ->with('user', 'user')
      ->willReturn([]);

    $config = $this->createMock(ImmutableConfig::class);
    $config->expects($this->any())
      ->method('get')
      ->with('role_mappings')
      ->willReturn($mappings);

    $this->configFactory->expects($this->any())
      ->method('get')
      ->with('openid_connect.settings')
      ->willReturn($config);

    $add_matcher = $this->exactly(count($add));
    $account->expects($add_matcher)
      ->method('addRole')
      ->willReturnCallback(function (string $param) use ($add, $add_matcher) {
        $this->assertSame($param, $add[$this->getInvocationCountHelper($add_matcher) - 1]);
      });

    $remove_matcher = $this->exactly(count($remove));
    $account->expects($remove_matcher)
      ->method('removeRole')
      ->willReturnCallback(function (string $param) use ($remove, $remove_matcher) {
        $this->assertSame($param, $remove[$this->getInvocationCountHelper($remove_matcher) - 1]);
      });

    $this->openIdConnect->saveUserinfo($account, ['userinfo' => $userinfo]);
  }

  /**
   * Helper to determine the number of invocations.
   *
   * @param \PHPUnit\Framework\MockObject\Rule\InvokedCount $count
   *   The invoked count object.
   *
   * @return int
   *   The number of invocations.
   */
  private function getInvocationCountHelper(InvokedCount $count): int {
    if (method_exists($count, 'getInvocationCount')) {
      // @todo Remove this once we drop support for Drupal ^10.
      trigger_deprecation('openid_connect', '3.x', 'The method InvocationMocker::getInvocationCount() is deprecated. Use numberOfInvocations() instead.');
      return $count->getInvocationCount();
    }

    return $count->numberOfInvocations();
  }

  /**
   * Data provider for the testRoleMappings method.
   *
   * @return array|array[]
   *   Array of parameters to pass to testRoleMappings().
   */
  public function dataProviderForTestRoleMappings(): array {
    return [
      'add groupX, remove groupY' => [
        'userinfo' => [
          'groups' => ['groupX'],
        ],
        'mappings' => [
          'role1' => ['groupX'],
          'role2' => ['groupY'],
        ],
        'add' => ['role1'],
        'remove' => ['role2'],
      ],
      'add groupX, groupY' => [
        'userinfo' => [
          'groups' => ['groupX', 'groupY'],
        ],
        'mappings' => [
          'role1' => ['groupX'],
          'role2' => ['groupY'],
        ],
        'add' => ['role1', 'role2'],
        'remove' => [],
      ],
      'remove groupX, groupY' => [
        'userinfo' => [
          'groups' => [],
        ],
        'mappings' => [
          'role1' => ['groupX'],
          'role2' => ['groupY'],
        ],
        'add' => [],
        'remove' => ['role1', 'role2'],
      ],
      'remove all groups when no groups in userinfo' => [
        'userinfo' => [],
        'mappings' => [
          'role1' => ['groupX'],
          'role2' => ['groupY'],
        ],
        'add' => [],
        'remove' => ['role1', 'role2'],
      ],
    ];
  }

}
