<?php

namespace Drupal\Tests\notify\Kernel;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Tests for sending notifications in batches.
 *
 * @group notify
 */
class BatchTest extends NotifyKernelTestBase {

  use ProphecyTrait;
  /**
   * Tests sending notifications in batches of 100.
   */
  public function testBatchSizeHundred() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Create 20 users that are notified of content.
    for ($i = 0; $i < 20; $i++) {
      $this->createNotifyUser();
    }

    // Set the last time a notification was sent to one day ago.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
    ]);

    // Create three nodes.
    $this->createNode();
    $this->createNode();
    $this->createNode();

    // Run cron and assert that twenty mails got sent. The mails should include
    // updates about node 1 to 3.
    notify_cron();
    $this->assertCount(20, $this->getMails(), 'Twenty emails are sent.');
    $this->assertMailString('body', '/node/1', 1);
    $this->assertMailString('body', '/node/2', 1);
    $this->assertMailString('body', '/node/3', 1);
  }

  /**
   * Tests sending notifications in batches of 3.
   */
  public function testBatchSizeThree() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Create 20 users that are notified of content.
    for ($i = 0; $i < 20; $i++) {
      $this->createNotifyUser();
    }

    // Reduce the batch size to 3.
    $this->setNotifySettings([
      'notify_batch' => 3,
    ]);

    // Set the last time a notification was sent to one day ago.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
    ]);

    // Create three nodes.
    $this->createNode();
    $this->createNode();
    $this->createNode();

    // Run cron and assert that three mails got sent. The mails should include
    // updates about node 1 to 3.
    notify_cron();
    $this->assertCount(3, $this->getMails(), 'Three emails are sent.');
    $this->assertMailString('body', '/node/1', 1);
    $this->assertMailString('body', '/node/2', 1);
    $this->assertMailString('body', '/node/3', 1);

    // Create a race condition, new post during cron. This will be node 4.
    // Do this by updating the current request time.
    $time = $this->prophesize(TimeInterface::class);
    $time->getRequestTime()
      ->willReturn($now + 2);
    $this->container->set('datetime.time', $time->reveal());
    $this->container->get('notify')->setDatetime($time->reveal());
    $this->createNode([
      'created' => $now + 2,
    ]);

    // Run cron a few more times.
    for ($i = 6; $i < 18; $i += 3) {
      notify_cron();
      $this->assertCount($i, $this->getMails());
    }

    // And another few times.
    notify_cron();
    notify_cron();
    notify_cron();
    $this->assertCount(20, $this->getMails(), 'Twenty emails are sent.');

    // Since now 4 nodes exist, the last mail should include updates about all
    // 4 nodes.
    $this->assertMailString('body', '/node/1', 1);
    $this->assertMailString('body', '/node/2', 1);
    $this->assertMailString('body', '/node/3', 1);
    $this->assertMailString('body', '/node/4', 1);

    // Change notify period and run cron again. The first three users who
    // earlier got an update about node 1 to 3, haven't received a notification
    // about node 4 yet and should get that now.
    $this->setNotifySettings([
      'notify_period' => 0,
    ]);
    notify_cron();
    $mails = $this->getMails();
    $this->assertCount(23, $mails, 'Twentythree emails are sent.');

    // Assert that the last mail only includes updates about node 4 and not
    // about node 1 to 3 since these were sent earlier already to these users.
    $mail = end($mails);
    $body = (string) $mail['body'];
    $this->assertStringNotContainsString('/node/1', $body);
    $this->assertStringNotContainsString('/node/2', $body);
    $this->assertStringNotContainsString('/node/3', $body);
    $this->assertStringContainsString('/node/4', $body);
  }

}
