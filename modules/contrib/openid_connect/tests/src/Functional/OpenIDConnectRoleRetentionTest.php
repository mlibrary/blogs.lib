<?php

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that users retain their Drupal roles after OpenID Connect login.
 *
 * @group openid_connect
 */
class OpenIDConnectRoleRetentionTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  const EXISTING_ROLE_ID = 'test_role';
  const ADMIN_ROLE_ID = 'admin_role';
  const ADMIN_ROLE_UNASSIGNED_ID = 'admin_role_unassigned';
  const EXISTING_GROUP_ID = 'test_group';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
  ];

  /**
   * A test user with custom roles.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The OpenID Connect client.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface
   */
  protected $client;

  /**
   * The openid_connect.openid_connect service.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIdConnect;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a test role.
    $this->createRole(['access content'], self::EXISTING_ROLE_ID, 'Test Role');
    $this->createRole(['access content'], self::ADMIN_ROLE_ID, 'Admin Role');
    $this->createRole(['access content'], self::ADMIN_ROLE_UNASSIGNED_ID, 'Unassigned Admin Role');

    // Create a test user with the custom role.
    $this->testUser = $this->drupalCreateUser(['access content', 'manage own openid connect accounts']);
    $this->testUser->addRole(self::EXISTING_ROLE_ID);
    $this->testUser->save();

    // Configure a generic OpenID Connect client.
    $this->client = $this->createTestClient('generic', 'Generic');

    // Get the HTTP client.
    $this->httpClient = $this->container->get('http_client');

    $this->openIdConnect = $this->container->get('openid_connect.openid_connect');
  }

  /**
   * Test the default role assignment behavior.
   *
   * @dataProvider dataProviderForTestForceResetRolesEnabledNoRoleMapping
   */
  public function testForceResetRolesEnabledNoRoleMapping(
    array $customData,
    array $assertions,
  ): void {
    // The default setting for force_reset_role_mappings is TRUE,
    // but let's enable it for readability of this test.
    $this->toggleForceResetRoleMappings(TRUE);
    $this->setRoleMappings();
    $this->roleAssignmentTests($customData, $assertions);
  }

  /**
   * Data provider for testForceResetRolesEnabledNoRoleMapping.
   *
   * This is the default behavior when role assignments are not configured.
   *
   * @return array[]
   *   Parameters for testForceResetRolesEnabledNoRoleMapping.
   */
  public static function dataProviderForTestForceResetRolesEnabledNoRoleMapping(): array {
    return [
      'User has a role and no `groups` array key is passed' => [
        [],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is passed and empty' => [
        [
          // Explicitly set empty groups array.
          'groups' => [],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is provided' => [
        [
          // Explicitly set groups array.
          'groups' => [self::EXISTING_GROUP_ID],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
    ];
  }

  /**
   * Test the mapped role assignment with the `force` option enabled.
   *
   * @dataProvider dataProviderForTestForceResetRolesEnabledRoleMappingDefined
   */
  public function testForceResetRolesEnabledRoleMappingDefined(
    array $customData,
    array $assertions,
  ): void {
    // The default setting for force_reset_role_mappings is TRUE,
    // but let's enable it for readability of this test.
    $this->toggleForceResetRoleMappings(TRUE);

    // Set a role mapping.
    $this->setRoleMappings([
      self::EXISTING_ROLE_ID => [self::EXISTING_GROUP_ID],
    ]);

    $this->roleAssignmentTests($customData, $assertions);
  }

  /**
   * Data provider for testForceResetRolesEnabledRoleMappingDefined.
   *
   * This is the default behavior when role assignments are configured.
   *
   * @return array[]
   *   Parameters for testForceResetRolesEnabledRoleMappingDefined.
   */
  public static function dataProviderForTestForceResetRolesEnabledRoleMappingDefined(): array {
    return [
      'User has a role and no `groups` array key is passed' => [
        [],
        [
          self::EXISTING_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is passed and empty' => [
        [
          // Explicitly set empty groups array.
          'groups' => [],
        ],
        [
          self::EXISTING_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is provided' => [
        [
          // Explicitly set groups array.
          'groups' => [self::EXISTING_GROUP_ID],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
    ];
  }

  /**
   * Test no role assignment with the `force` option disabled.
   *
   * @dataProvider dataProviderForTestForceResetRolesDisabledNoRoleMapping
   */
  public function testForceResetRolesDisabledNoRoleMapping(
    array $customData,
    array $assertions,
  ): void {
    // Disable the force_reset_role_mappings setting.
    $this->toggleForceResetRoleMappings(FALSE);
    // Set a role mapping to none.
    $this->setRoleMappings();
    $this->roleAssignmentTests($customData, $assertions);
  }

  /**
   * Data provider for testForceResetRolesEnabledRoleMappingDefined.
   *
   * This is the default behavior when role assignments are configured.
   *
   * @return array[]
   *   Parameters for testForceResetRolesDisabledNoRoleMapping.
   */
  public static function dataProviderForTestForceResetRolesDisabledNoRoleMapping(): array {
    return [
      'User has a role and no `groups` array key is passed' => [
        [],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is passed and empty' => [
        [
          // Explicitly set empty groups array.
          'groups' => [],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is provided' => [
        [
          // Explicitly set groups array.
          'groups' => [self::EXISTING_GROUP_ID],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
    ];
  }

  /**
   * Test role assignment with the `force` option disabled.
   *
   * @dataProvider dataProviderForTestForceResetRolesDisabledWithRoleMapping
   */
  public function testForceResetRolesDisabledWithRoleMapping(
    array $customData,
    array $assertions,
  ): void {
    // Disable the force_reset_role_mappings setting.
    $this->toggleForceResetRoleMappings(FALSE);
    // Set a role mapping to none.
    $this->setRoleMappings([
      self::EXISTING_ROLE_ID => [self::EXISTING_GROUP_ID],
    ]);

    $this->roleAssignmentTests($customData, $assertions);
  }

  /**
   * Data provider for testForceResetRolesDisabledWithRoleMapping.
   *
   * This is the default behavior when role assignments are configured.
   *
   * @return array[]
   *   Parameters for testForceResetRolesDisabledWithRoleMapping.
   */
  public static function dataProviderForTestForceResetRolesDisabledWithRoleMapping(): array {
    return [
      'User has a role and no `groups` array key is passed' => [
        [],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is passed and empty' => [
        [
          // Explicitly set empty groups array.
          'groups' => [],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
      'User has a role and the `groups` array key is provided' => [
        [
          // Explicitly set groups array.
          'groups' => [self::EXISTING_GROUP_ID],
        ],
        [
          self::EXISTING_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => TRUE,
        ],
      ],
    ];
  }

  /**
   * Role assignment test setup.
   *
   * @param array $customData
   *   Array of data to append to a mocked OpenID Connect userinfo.
   * @param array $assertions
   *   The role assignment expectations.
   */
  protected function roleAssignmentTests(
    array $customData,
    array $assertions,
  ): void {
    // Give the user an admin role.
    $this->testUser->addRole(self::ADMIN_ROLE_ID);

    // Define custom user data with no groups.
    $custom_data = [
      'sub' => 'user-' . $this->testUser->id(),
      'name' => $this->testUser->getAccountName(),
      'email' => $this->testUser->getEmail(),
    ];

    $userInfo = array_merge($custom_data, $customData);

    // Verify the user has the test role before we start.
    $this->assertTrue($this->testUser->hasRole(self::EXISTING_ROLE_ID), 'User has the test role before OpenID Connect processing.');
    $this->assertTrue($this->testUser->hasRole(self::ADMIN_ROLE_ID), 'User has the admin role before OpenID Connect processing.');
    $this->assertFalse($this->testUser->hasRole(self::ADMIN_ROLE_UNASSIGNED_ID), 'User does not have the unassigned admin role before OpenID Connect processing.');

    $context = [
      'userinfo' => $userInfo,
      'tokens' => [
        'id_token' => 'mock_id_token',
        'access_token' => 'mock_access_token',
      ],
      'plugin_id' => 'generic',
      'sub' => 'user-' . $this->testUser->id(),
    ];

    // Directly call the saveUserinfo method
    // to simulate what happens during login.
    $result = $this->openIdConnect->saveUserinfo($this->testUser, $context);
    $this->assertTrue($result, 'User info was saved successfully.');

    // Reload the user entity to get fresh data.
    $this->testUser = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->load($this->testUser->id());

    // Verify the user still has the test role after processing.
    $this->assertEquals($assertions[self::EXISTING_ROLE_ID], $this->testUser->hasRole(self::EXISTING_ROLE_ID), 'Test role');
    $this->assertEquals($assertions[self::ADMIN_ROLE_ID], $this->testUser->hasRole(self::ADMIN_ROLE_ID), 'Admin role');
    $this->assertFalse($this->testUser->hasRole(self::ADMIN_ROLE_UNASSIGNED_ID), 'User does not have the unassigned admin role after OpenID Connect processing.');
  }

}
