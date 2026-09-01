<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Kernel;

use Drupal\node\Entity\Node;

/**
 * Test covering the UnPublishedStateConstraintValidator.
 *
 * @coversDefaultClass \Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint\UnPublishStateConstraintValidator
 *
 * @group scheduler_content_moderation_integration
 */
class UnPublishedStateConstraintTest extends SchedulerContentModerationTestBase {

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
   * Test published to unpublished transition.
   *
   * Test valid scheduled publishing state to valid scheduled un-publish
   * state transitions.
   *
   * @covers ::validate
   */
  public function testValidPublishStateToUnPublishStateTransition(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'draft',
      'unpublish_on' => strtotime('+3 days'),
      'publish_on' => strtotime('+2 days'),
      'unpublish_state' => 'archived',
      'publish_state' => 'published',
    ]);

    $violations = $node->validate();
    $this->assertCount(0, $violations, 'Both transitions should pass validation');
  }

  /**
   * Test an invalid un-publish transition.
   *
   * Test an invalid un-publish transition from a nodes current moderation
   * state.
   *
   * @cover ::validate
   */
  public function testInvalidUnPublishStateTransition(): void {
    // Check cases when a publish_state has been selected and not selected.
    // No publish_on date been entered, so they should fail validation.
    foreach (['', '_none', 'published'] as $publish_state) {
      $node = Node::create([
        'type' => 'example',
        'title' => 'Test title',
        'moderation_state' => 'draft',
        'publish_state' => $publish_state,
        'unpublish_on' => strtotime('tomorrow'),
        'unpublish_state' => 'archived',
      ]);

      // Assert that the change from draft to archived fails validation.
      $violations = $node->validate();
      $message = (count($violations) > 0) ? $violations->get(0)->getMessage() : 'No violation message found';
      $this->assertEquals('The scheduled un-publishing state of Archived is not a valid transition from the current moderation state of Draft for this content.', strip_tags($message));
    }
  }

  /**
   * Test invalid transition.
   *
   * Test invalid transition from scheduled published to scheduled un-published
   * state.
   *
   * @covers ::validate
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testInvalidPublishStateToUnPublishStateTransition(): void {
    // Add a second published state, and a transition to it from draft, but no
    // transition from it to archived.
    $this->workflow->getTypePlugin()
      ->addState('published_2', 'Published 2')
      ->addTransition('published_2', 'Published 2', ['draft'], 'published_2');

    $config = $this->workflow->getTypePlugin()->getConfiguration();
    $config['states']['published_2']['published'] = TRUE;
    $config['states']['published_2']['default_revision'] = TRUE;

    $this->workflow->getTypePlugin()->setConfiguration($config);
    $this->workflow->save();

    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'draft',
      'publish_on' => strtotime('tomorrow'),
      'unpublish_on' => strtotime('+2 days'),
      'unpublish_state' => 'archived',
      'publish_state' => 'published_2',
    ]);

    // Check that the attempted scheduled transition from the new published_2
    // state to archived fails validation.
    $violations = $node->validate();
    $this->assertCount(1, $violations, 'The transition from published 2 to archived should fail validation');
    $message = (count($violations) > 0) ? $violations->get(0)->getMessage() : 'No violation message found';
    $this->assertEquals('The scheduled un-publishing state of Archived is not a valid transition from the scheduled publishing state of Published 2.', strip_tags($message));
  }

  /**
   * Test that scheduling unpublish to the current state fails validation.
   *
   * When no publish state is scheduled, the unpublish state should not match
   * the current moderation state as the content is already in that state.
   *
   * @covers ::validate
   */
  public function testSameStateAsScheduledUnpublishState(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'archived',
      'unpublish_on' => strtotime('tomorrow'),
      'unpublish_state' => 'archived',
    ]);

    $violations = $node->validate();
    $found = FALSE;
    foreach ($violations as $violation) {
      if (str_contains(strip_tags((string) $violation->getMessage()), 'already in the Archived state')) {
        $found = TRUE;
        break;
      }
    }
    $this->assertTrue($found);
  }

  /**
   * Test same-state check is skipped when a publish state is also scheduled.
   *
   * When both publish and unpublish are scheduled, the current state matching
   * the unpublish state is valid because the content will transition through
   * the publish state first.
   *
   * @covers ::validate
   */
  public function testSameUnpublishStateAllowedWithPublishState(): void {
    $node = Node::create([
      'type' => 'example',
      'title' => 'Test title',
      'moderation_state' => 'archived',
      'publish_on' => strtotime('tomorrow'),
      'publish_state' => 'published',
      'unpublish_on' => strtotime('+2 days'),
      'unpublish_state' => 'archived',
    ]);

    $violations = $node->validate();
    foreach ($violations as $violation) {
      $this->assertStringNotContainsString('already in the', strip_tags((string) $violation->getMessage()));
    }
  }

}
