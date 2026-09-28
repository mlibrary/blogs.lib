<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests role assignment for new users created via OpenID Connect.
 *
 * @group openid_connect
 */
class OpenIDConnectRoleAssignmentTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  const ASSIGNABLE_ROLE_ID = 'content_editor';
  const ADMIN_ROLE_ID = 'admin_role';
  const VIEWER_ROLE_ID = 'content_viewer';
  const EDITOR_GROUP_ID = 'editors';
  const ADMIN_GROUP_ID = 'administrators';
  const VIEWER_GROUP_ID = 'viewers';

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

    // Create test roles.
    $this->createRole(['access content'], self::ASSIGNABLE_ROLE_ID, 'Content Editor');
    $this->createRole(['administer users'], self::ADMIN_ROLE_ID, 'Admin Role');
    $this->createRole(['access content'], self::VIEWER_ROLE_ID, 'Content Viewer');

    // Configure a generic OpenID Connect client.
    $this->client = $this->createTestClient('generic', 'Generic');

    $this->openIdConnect = $this->container->get('openid_connect.openid_connect');
  }

  /**
   * Test role assignment for new users with force reset enabled.
   *
   * @dataProvider dataProviderForTestNewUserRoleAssignmentForceEnabled
   */
  public function testNewUserRoleAssignmentForceEnabled(
    array $groups,
    array $expectedRoles,
    string $description,
  ): void {
    // Enable force reset role mappings.
    $this->toggleForceResetRoleMappings(TRUE);

    // Set up role mappings.
    $this->setRoleMappings([
      self::ASSIGNABLE_ROLE_ID => [self::EDITOR_GROUP_ID],
      self::ADMIN_ROLE_ID => [self::ADMIN_GROUP_ID],
      self::VIEWER_ROLE_ID => [self::VIEWER_GROUP_ID],
    ]);

    $this->performNewUserRoleAssignmentTest($groups, $expectedRoles, $description);
  }

  /**
   * Data provider for testNewUserRoleAssignmentForceEnabled.
   *
   * @return array[]
   *   Test parameters.
   */
  public static function dataProviderForTestNewUserRoleAssignmentForceEnabled(): array {
    return [
      'New user with single matching group gets assigned role' => [
        [
          self::EDITOR_GROUP_ID,
        ],
        [
          self::ASSIGNABLE_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User in editors group should get content_editor role',
      ],
      'New user with multiple matching groups gets multiple roles' => [
        [
          self::EDITOR_GROUP_ID,
          self::VIEWER_GROUP_ID,
        ],
        [
          self::ASSIGNABLE_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => TRUE,
        ],
        'User in multiple groups should get multiple roles',
      ],
      'New user with admin group gets admin role' => [
        [
          self::ADMIN_GROUP_ID,
        ],
        [
          self::ASSIGNABLE_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => TRUE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User in admin group should get admin role',
      ],
      'New user with no matching groups gets no additional roles' => [
        ['unknown_group'],
        [
          self::ASSIGNABLE_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User with unmapped groups should get no additional roles',
      ],
      'New user with empty groups array gets no additional roles' => [
        [],
        [
          self::ASSIGNABLE_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User with empty groups should get no additional roles',
      ],
    ];
  }

  /**
   * Test role assignment for new users with force reset disabled.
   *
   * @dataProvider dataProviderForTestNewUserRoleAssignmentForceDisabled
   */
  public function testNewUserRoleAssignmentForceDisabled(
    array $groups,
    array $expectedRoles,
    string $description,
  ): void {
    // Disable force reset role mappings.
    $this->toggleForceResetRoleMappings(FALSE);

    // Set up role mappings.
    $this->setRoleMappings([
      self::ASSIGNABLE_ROLE_ID => [self::EDITOR_GROUP_ID],
      self::ADMIN_ROLE_ID => [self::ADMIN_GROUP_ID],
      self::VIEWER_ROLE_ID => [self::VIEWER_GROUP_ID],
    ]);

    $this->performNewUserRoleAssignmentTest($groups, $expectedRoles, $description);
  }

  /**
   * Data provider for testNewUserRoleAssignmentForceDisabled.
   *
   * @return array[]
   *   Test parameters.
   */
  public static function dataProviderForTestNewUserRoleAssignmentForceDisabled(): array {
    return [
      'New user with matching group gets assigned role when groups provided' => [
        [
          self::EDITOR_GROUP_ID,
        ],
        [
          self::ASSIGNABLE_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User in editors group should get content_editor role',
      ],
      'New user with multiple matching groups gets multiple roles when groups provided' => [
        [
          self::EDITOR_GROUP_ID,
          self::VIEWER_GROUP_ID,
        ],
        [
          self::ASSIGNABLE_ROLE_ID => TRUE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => TRUE,
        ],
        'User in multiple groups should get multiple roles',
      ],
      'New user with empty groups gets no additional roles' => [
        [],
        [
          self::ASSIGNABLE_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User with empty groups should get no additional roles',
      ],
    ];
  }

  /**
   * Test role assignment without role mappings configured.
   *
   * @dataProvider dataProviderForTestNewUserNoRoleMappings
   */
  public function testNewUserNoRoleMappings(
    array $groups,
    array $expectedRoles,
    string $description,
  ): void {
    // Enable force reset role mappings.
    $this->toggleForceResetRoleMappings(TRUE);

    // Set no role mappings.
    $this->setRoleMappings([]);

    $this->performNewUserRoleAssignmentTest($groups, $expectedRoles, $description);
  }

  /**
   * Data provider for testNewUserNoRoleMappings.
   *
   * @return array[]
   *   Test parameters.
   */
  public static function dataProviderForTestNewUserNoRoleMappings(): array {
    return [
      'New user with groups but no role mappings gets no additional roles' => [
        [
          self::EDITOR_GROUP_ID,
          self::ADMIN_GROUP_ID,
        ],
        [
          self::ASSIGNABLE_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User should get no additional roles when no mappings are configured',
      ],
      'New user with no groups and no role mappings gets no additional roles' => [
        [],
        [
          self::ASSIGNABLE_ROLE_ID => FALSE,
          self::ADMIN_ROLE_ID => FALSE,
          self::VIEWER_ROLE_ID => FALSE,
        ],
        'User should get no additional roles when no groups or mappings',
      ],
    ];
  }

  /**
   * Perform role assignment test for new users.
   *
   * @param array $groups
   *   The groups to assign to the user.
   * @param array $expectedRoles
   *   Expected role assignments.
   * @param string $description
   *   Test description.
   */
  protected function performNewUserRoleAssignmentTest(
    array $groups,
    array $expectedRoles,
    string $description,
  ): void {
    // Create a unique email for this test to ensure a new user.
    $email = $this->randomMachineName() . '@example.com';
    $sub = 'user_' . $this->randomMachineName();

    // Create userinfo data.
    $userinfo = [
      'sub' => $sub,
      'name' => 'Test User',
      'email' => $email,
      'groups' => $groups,
    ];

    // Create a new user using the OpenID Connect service.
    $new_user = $this->openIdConnect->createUser($sub, $userinfo, $this->client->id());

    // Create context for saveUserinfo.
    $context = [
      'userinfo' => $userinfo,
      'tokens' => [
        'id_token' => 'mock_id_token',
        'access_token' => 'mock_access_token',
      ],
      'plugin_id' => 'generic',
      'sub' => $sub,
      'is_new' => TRUE,
    ];

    // Save user info to trigger role assignment.
    $result = $this->openIdConnect->saveUserinfo($new_user, $context);
    $this->assertTrue($result, 'User info was saved successfully');

    // Reload the user to get fresh data.
    $new_user = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->load($new_user->id());

    // Verify role assignments.
    foreach ($expectedRoles as $role => $expected) {
      $has_role = $new_user->hasRole($role);
      $this->assertEquals($expected, $has_role, "$description - Role: $role");
    }

    // Verify the user always has the authenticated role.
    $this->assertTrue($new_user->hasRole('authenticated'), 'New user has authenticated role');
  }

}
