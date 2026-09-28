<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Kernel;

use Drupal\node\Entity\Node;

/**
 * Test covering the PublishedStateConstraintValidator.
 *
 * @coversDefaultClass \Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint\PublishStateConstraintValidator
 *
 * @group scheduler_content_moderation_integration
 */
class PublishedStateConstraintTest extends SchedulerContentModerationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $user->method('hasPermission')->willReturn(TRUE);
    $this->container->set('current_user', $user);
  }

  /**
   * Test valid publish state transitions.
   *
   * @covers ::validate
   */
  public function testValidPublishStateTransition(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'draft',
      'publish_on' => strtotime('tomorrow'),
      'publish_state' => 'published',
    ]);

    // Assert that the publish state passes validation.
    $violations = $node->validate();
    $this->assertCount(0, $violations);
  }

  /**
   * Test invalid publish state transitions.
   *
   * @covers ::validate
   */
  public function testInvalidPublishStateTransition(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'draft',
      'publish_on' => strtotime('tomorrow'),
      'publish_state' => 'archived',
    ]);

    // Assert that the invalid publish state fails validation, we get two
    // violations since the draft state does not exist.
    $violations = $node->validate();
    $this->assertEquals('The scheduled publishing state of Archived is not a valid transition from the current moderation state of Draft for this content.', strip_tags($violations->get(0)->getMessage()));

    // @todo Figure out how to actually test this with valid options that don't
    // break the select list widget but still test the invalid transition.
    // $this->assertCount(1, $violations);
  }

  /**
   * Test that saving in the same state as the scheduled publish state fails.
   *
   * @covers ::validate
   */
  public function testSameStateAsScheduledPublishState(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'published',
      'publish_on' => strtotime('tomorrow'),
      'publish_state' => 'published',
    ]);

    $violations = $node->validate();
    $found = FALSE;
    foreach ($violations as $violation) {
      if (str_contains(strip_tags((string) $violation->getMessage()), 'already being saved as Published')) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);
  }

  /**
   * Test that saving in a different state than scheduled publish state passes.
   *
   * @covers ::validate
   */
  public function testDifferentStateFromScheduledPublishState(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'draft',
      'publish_on' => strtotime('tomorrow'),
      'publish_state' => 'published',
    ]);

    $violations = $node->validate();
    foreach ($violations as $violation) {
      $this->assertStringNotContainsString('already being saved as', strip_tags((string) $violation->getMessage()));
    }
  }

}
