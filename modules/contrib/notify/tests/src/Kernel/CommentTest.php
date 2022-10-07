<?php

namespace Drupal\Tests\notify\Kernel;

use Drupal\notify\NotifyInterface;

/**
 * Tests sending notifications about comments.
 *
 * @group notify
 */
class CommentTest extends NotifyKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'comment',
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'node',
    'notify',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);

    $this->installCommentField();
  }

  /**
   * Tests sending notifications about comments.
   */
  public function testSendNotificationsAboutNewComments() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Configure the module to always send notifications.
    $this->setNotifySettings([
      'notify_period' => NotifyInterface::PERIOD_ALWAYS,
    ]);

    // Create an account that can receive notifications about new comments.
    $account = $this->createNotifyUser([], [
      'node' => 0,
      'comment' => 1,
    ]);

    // Create a node.
    $node = $this->createNode();

    // Send notify batch. Assert that no notifications are sent, because no
    // users wanted to receive updates about nodes.
    notify_cron();
    $this->assertCount(0, $this->getMails(), 'No emails are sent.');

    // Set the last time a notification were sent to one day ago so
    // notifications can get send on the next cron run.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
    ]);

    // Add a comment to the node.
    $this->createComment($node->id());

    // Send notifications again. Now the user should receive a notification
    // about a new comment.
    notify_cron();
    $this->assertCount(1, $this->getMails(), 'One email is sent.');
  }

}
