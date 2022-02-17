<?php

namespace Drupal\Tests\og_menu\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og_menu\Entity\OgMenu;
use Drupal\og_menu\Entity\OgMenuInstance;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests access to operations on OG Menu instances.
 *
 * @group og_menu
 */
class OgMenuAccessTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_test',
    'field',
    'og',
    'og_menu',
    'system',
    'user',
  ];

  /**
   * An array of test users.
   *
   * @var \Drupal\user\Entity\User[]
   */
  protected $users;

  /**
   * A test group.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $group;

  /**
   * The bundle ID of the test group.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * A test OG Menu.
   *
   * @var \Drupal\og_menu\Entity\OgMenu
   */
  protected $ogMenu;

  /**
   * A test OG Menu instance.
   *
   * @var \Drupal\og_menu\Entity\OgMenuInstance
   */
  protected $ogMenuInstance;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig(['og']);
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('og_membership');
    $this->installEntitySchema('ogmenu');
    $this->installEntitySchema('ogmenu_instance');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');

    // Create a first user. This will be UID 1 who has super powers.
    $this->users['uid1'] = User::create(['name' => $this->randomString()]);
    $this->users['uid1']->save();

    // Create an 'OG administrator' user who has the global 'administer group'
    // permission. This user should be able to access everything related to any
    // group.
    /** @var RoleInterface $og_menu_admin_role */
    $admin_role = Role::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $admin_role
      ->grantPermission('administer group')
      ->save();
    $this->users['ogadmin'] = User::create([
      'name' => $this->randomString(),
      'roles' => [$admin_role->id()],
    ]);
    $this->users['ogadmin']->save();

    // Create an 'OG Menu administrator' user who will get the global
    // permissions to manage any OG menu.
    /** @var RoleInterface $og_menu_admin_role */
    $og_menu_admin_role = Role::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
    ]);
    $og_menu_admin_role
      ->grantPermission('administer og menu')
      ->save();

    $this->users['ogmenuadmin'] = User::create([
      'name' => $this->randomString(),
      'roles' => [$og_menu_admin_role->id()],
    ]);
    $this->users['ogmenuadmin']->save();

    // Create a 'group administrator' user who is a normal authenticated user
    // but has the administrator role within a particular group. By default this
    // user should be able to access everything related to their own group.
    $this->users['groupadmin'] = User::create(['name' => $this->randomString()]);
    $this->users['groupadmin']->save();

    // Create a 'group member' user who is a normal authenticated user and a
    // member of a particular group. This user should only be able to access
    // operations for which permission has been explicitly granted.
    $this->users['groupmember'] = User::create(['name' => $this->randomString()]);
    $this->users['groupmember']->save();

    // Create an 'authenticated' user who does not have any special permissions.
    $this->users['authenticated'] = User::create(['name' => $this->randomString()]);
    $this->users['authenticated']->save();

    // Create a test group. We use the 'entity test' entity which is a bit
    // easier to set up since it has fake bundles.
    $this->groupBundle = mb_strtolower($this->randomMachineName());
    Og::groupTypeManager()->addGroup('entity_test', $this->groupBundle);

    // Create a group and associate with the group administrator. This user will
    // be subscribed to the group and inherit the administrator role
    // automatically.
    $this->group = EntityTest::create([
      'type' => $this->groupBundle,
      'name' => $this->randomString(),
      'user_id' => $this->users['groupadmin']->id(),
    ]);
    $this->group->save();

    // Subscribe the group member to the group.
    /** @var OgMembership $membership */
    $membership = OgMembership::create(['type' => OgMembershipInterface::TYPE_DEFAULT]);
    $membership
      ->setOwner($this->users['groupmember'])
      ->setGroup($this->group)
      ->save();

    // Add an OG Menu.
    $this->ogMenu = OgMenu::create([
      'label' => $this->randomString(),
      'id' => $this->randomMachineName(),
    ]);
    $this->ogMenu->save();

    // Add an OG Menu Instance.
    $this->ogMenuInstance = OgMenuInstance::create([
      'id' => $this->randomMachineName(),
      'type' => $this->ogMenu->id(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $this->group->id(),
    ]);
    $this->ogMenuInstance->save();
  }

  /**
   * Test access to the administration section.
   */
  public function testOgMenuEntityAccess() {
    $expected_operations = [
      'uid1' => [
        'create' => TRUE,
        'update' => TRUE,
        'view' => TRUE,
        'delete' => TRUE,
      ],
      'ogadmin' => [
        'create' => FALSE,
        'update' => FALSE,
        'view' => FALSE,
        'delete' => FALSE,
      ],
      'ogmenuadmin' => [
        'create' => TRUE,
        'update' => TRUE,
        'view' => TRUE,
        'delete' => TRUE,
      ],
      'groupadmin' => [
        'create' => FALSE,
        'update' => FALSE,
        'view' => FALSE,
        'delete' => FALSE,
      ],
      'groupmember' => [
        'create' => FALSE,
        'update' => FALSE,
        'view' => FALSE,
        'delete' => FALSE,
      ],
      'authenticated' => [
        'create' => FALSE,
        'update' => FALSE,
        'view' => FALSE,
        'delete' => FALSE,
      ],
    ];

    foreach ($expected_operations as $user_key => $operations) {
      \Drupal::currentUser()->setAccount($this->users[$user_key]);
      foreach ($operations as $operation => $expected) {
        $message = "User $user_key " . ($expected ? 'has' : 'does not have') . " access to the $operation operation.";
        $this->assertEquals($expected, $this->ogMenu->access($operation), $message);

      }
    }
    $this->assertFalse($this->ogMenu->access('update'));
  }

  /**
   * Tests deletion of unsaved entities.
   */
  public function testDeleteUnsavedAccess() {
    // Create an OG Menu but do not save it.
    $ogmenu = OgMenu::create([
      'label' => $this->randomString(),
      'id' => $this->randomMachineName(),
    ]);

    // Create an OG Menu Instance but don't save it.
    $ogmenu_instance = OgMenuInstance::create([
      'id' => $this->randomMachineName(),
      'type' => $ogmenu->id(),
    ]);

    // Note we are not testing UID 1 which has access to everything, even to
    // nonsensical operations such as this.
    $user_keys = [
      'ogadmin',
      'ogmenuadmin',
      'groupadmin',
      'groupmember',
      'authenticated',
    ];

    foreach ($user_keys as $user_key) {
      \Drupal::currentUser()->setAccount($this->users[$user_key]);
      foreach (['ogmenu', 'ogmenu_instance'] as $entity_type) {
        $message = "User $user_key should not be able to delete an unsaved $entity_type entity.";
        $this->assertFalse($$entity_type->access('delete'), $message);
      }
    }
  }

  /**
   * Tests that unsupported operations do not grant access.
   */
  public function testUnsupportedOperation() {
    // In the context of a group UID1 and the OG admin are 'superadmins' which
    // have access to everything, even to unsupported operations.
    $user_keys = [
      'uid1' => TRUE,
      'ogadmin' => TRUE,
      'ogmenuadmin' => FALSE,
      'groupadmin' => FALSE,
      'groupmember' => FALSE,
      'authenticated' => FALSE,
    ];

    foreach ($user_keys as $user_key => $expected_access) {
      $message = "User $user_key should " . ($expected_access ? '' : 'not') . " be granted access to an unsupported operation by default.";
      $this->assertEquals($expected_access, $this->ogMenuInstance->access('some-non-existing-operation', $this->users[$user_key]), $message);
    }
  }

}
