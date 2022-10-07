<?php

namespace Drupal\Tests\notify\Kernel;

use Drupal\notify\NotifyInterface;

function mylog($var, $label = NULL) {
  $tmp_file = '/tmp/my_debug.log';
  $output = '';
  if(!is_null($label)) {
    $output = $label . ': ';
  }
  $output .= print_r($var, 1) . PHP_EOL;
  file_put_contents($tmp_file, $output, FILE_APPEND);
}

/**
 * Tests the skip flags feature.
 *
 * @group notify
 */
class SkipFlagsTest extends NotifyKernelTestBase {

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
   * A regular user that receives notifications about new content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Install comment stuff.
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['comment']);
    $this->installCommentField();

    // Configure the module to always send notifications.
    $this->setNotifySettings([
      'notify_period' => NotifyInterface::PERIOD_ALWAYS,
    ]);
    // Subscribe new users to articles by default.
    $this->setNotifySettings([
      'notify_nodetypes' => [
        'article' => 1,
      ],
    ]);

    // Create an user that receives notifications.
    $this->account = $this->createNotifyUser();
  }

  /**
   * Tests that no notifications are send when all is flagged as skipped.
   */
  public function testSkipAll() {
    // Create a node and skip it.
    $node = $this->createNode();
    $this->container->get('notify')->skipNode($node);

    // Create a comment for the node and skip it too.
    $comment = $this->createComment($node->id());
    $this->container->get('notify')->skipComment($comment);

    notify_cron();
    $this->assertCount(0, $this->getMails(), 'No emails are sent.');
  }

  /**
   * Tests that notifications are send, but that one node is skipped.
   */
  public function testSkipSingleNode() {
    // Create two nodes.
    $node1 = $this->createNode();
    $node2 = $this->createNode();

    // Skip notification for node 2.
    $this->container->get('notify')->skipNode($node2);

    notify_cron();
    $this->assertCount(1, $this->getMails(), 'One email is sent.');

    // Assert that node 1 is in the notification, but node 2 is not.
    $mails = $this->getMails();
    $mail = end($mails);
    $body = (string) $mail['body'];
    $this->assertStringContainsString('/node/1', $body);
    $this->assertStringNotContainsString('/node/2', $body);
  }

  /**
   * Tests that notifications are sent, but that one comment is skipped.
   */
  public function testSkipSingleComment() {

    // Create a node.
    $node1 = $this->createNode();
    $node2 = $this->createNode();

    // Create a few comments.
    $comment1 = $this->createComment($node1->id());
    $comment2 = $this->createComment($node1->id());
    $comment3 = $this->createComment($node2->id());

    // Skip the second and third comment.
    $this->container->get('notify')->skipComment($comment2);
    $this->container->get('notify')->skipComment($comment3);

    notify_cron();
    $this->assertCount(1, $this->getMails(), 'One email is sent.');

    // Assert that comment 1 is in the notification, but the others are not.
    $mails = $this->getMails();
    $mail = end($mails);
    $body = (string) $mail['body'];
    $this->assertStringContainsString('1 new comment', $body);
    $this->assertStringContainsString('/comment/1#comment-1', $body);
    $this->assertStringNotContainsString('/comment/2#comment-2', $body);

  }

}
