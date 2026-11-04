<?php

namespace Drupal\Tests\scheduler\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests access to the Scheduler date fields.
 *
 * @group scheduler_kernel
 */
#[Group('scheduler_kernel')]
#[RunTestsInSeparateProcesses]
class SchedulerFieldAccessTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'field',
    'node',
    'system',
    'text',
    'user',
    'views',
    'scheduler',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['scheduler']);
    $this->installEntitySchema('user');

    // Create a content type which is not enabled for scheduling.
    NodeType::create(['type' => 'page', 'name' => 'Page'])
      ->setThirdPartySetting('scheduler', 'publish_enable', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE)
      ->save();
  }

  /**
   * Tests edit access to the date fields without an entity.
   */
  public function testFieldDefinitionAccess(): void {
    $definitions = $this->container->get('entity_field.manager')->getFieldDefinitions('node', 'page');
    $access_handler = $this->container->get('entity_type.manager')->getAccessControlHandler('node');
    // Set the user IDs explicitly so that neither user is user 1, which has all
    // permissions, and so that Drupal 9 does not need the sequences table.
    $scheduling_user = $this->createUser(['schedule publishing of nodes'], NULL, FALSE, ['uid' => 2]);
    $other_user = $this->createUser([], NULL, FALSE, ['uid' => 3]);
    $node = Node::create(['type' => 'page', 'title' => 'Page']);

    foreach (['publish_on', 'unpublish_on'] as $field_name) {
      $definition = $definitions[$field_name];

      // Without the scheduling permission the field cannot be edited.
      $this->assertTrue($access_handler->fieldAccess('edit', $definition, $other_user, NULL, TRUE)->isForbidden(), "$field_name is forbidden without the permission.");

      // The content type settings can only be checked for a specific entity,
      // so the field definition is editable with the permission even though
      // the content type is not enabled for scheduling.
      $this->assertTrue($access_handler->fieldAccess('edit', $definition, $scheduling_user, NULL, TRUE)->isAllowed(), "$field_name is allowed for the field definition.");
      $this->assertTrue($access_handler->fieldAccess('edit', $definition, $scheduling_user, $node->get($field_name), TRUE)->isForbidden(), "$field_name is forbidden for an entity of a content type which is not enabled.");
    }
  }

}
