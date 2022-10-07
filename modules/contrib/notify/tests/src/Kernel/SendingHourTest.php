<?php

namespace Drupal\Tests\notify\Kernel;

use Prophecy\PhpUnit\ProphecyTrait;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Various notify test cases for the sending hour being respected.
 *
 * @group notify
 */
class SendingHourTest extends NotifyKernelTestBase {

  use ProphecyTrait;
  /**
   * A prophesized datetime service.
   *
   * @var \Prophecy\Prophecy\ProphecyInterface|\Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Prophesize the datetime service so we can manipulate what the code
    // believes that the current time is.
    $this->time = $this->prophesize(TimeInterface::class);

    // Subscribe new users to articles by default.
    $this->setNotifySettings([
      'notify_nodetypes' => [
        'article' => 1,
      ],
    ]);

    // Create a few users.
    $this->createNotifyUser([
      'administer nodes',
    ]);
    $this->createNotifyUser();
    $this->createNotifyUser();

    // Configure to not send notifications about updated content.
    $this->setNotifySettings([
      'notify_include_updates' => 0,
    ]);
  }

  /**
   * Tests whether sending time is respected.
   */
  public function testRespectSendingHour() {
    // Set notify period to 2 days.
    // Set the sending hour to 3 o'clock.
    $this->setNotifySettings([
      'notify_period' => (3600 * 48),
      'notify_send_hour' => 3,
    ]);

    // Set the current time to 5 o'clock.
    $now = strtotime('2022-01-01T05:00:00');
    $this->time->getRequestTime()
      ->willReturn($now);

    // And override the time service.
    $this->container->set('datetime.time', $this->time->reveal());

    // Create three nodes:
    // - One that is a week old;
    // - One that is 5 hours old;
    // - And one that is 1 hour old.
    $this->createNode([
      'created' => $now - (3600 * 24 * 7),
    ]);
    $this->createNode([
      'created' => $now - (3600 * 5),
    ]);
    $this->createNode([
      'created' => $now - 3600,
    ]);

    // Set the last time a notification was sent to one day ago minus 10
    // minutes. This means that a full day should pass before notifications may
    // be send again.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24) - 10,
      'notify_cron_next' => $now,
    ]);

    // Run cron and assert that no mails got sent.
    notify_cron();
    $this->assertCount(0, $this->getMails(), 'No emails are sent.');

    // Set next scheduled notification time to 100 minutes ago.
    // Set the last time a notification was sent to one day ago minus 10 minutes.
    $this->setNotifyStates([
      'notify_send_start' => 0,
      'notify_send_last' => $now - (3600 * 24) - 10,
      'notify_cron_next' => $now - 6000,
    ]);

    // Run cron and assert that three mails got sent.
    notify_cron();
    $this->assertCount(3, $this->getMails(), 'Three emails are sent.');
  }

  /**
   * Tests whether sending time is respected.
   */
  public function testSendingHourNoneToSend() {
    // Set notify period to 2 days.
    // Set the sending hour to 3 o'clock.
    $this->setNotifySettings([
      'notify_period' => (3600 * 48),
      'notify_send_hour' => 3,
    ]);

    // Set the current time to 5 o'clock.
    $now = strtotime('2022-01-01T05:00:00');
    $this->time->getRequestTime()
      ->willReturn($now);

    // And override the time service.
    $this->container->set('datetime.time', $this->time->reveal());

    // Create three nodes that are 1 hour old.
    $this->createNode([
      'created' => $now - 3600,
    ]);
    $this->createNode([
      'created' => $now - 3600,
    ]);
    $this->createNode([
      'created' => $now - 3600,
    ]);

    // Set the last time a notification was sent to one day ago minus 10
    // minutes. And schedule the next time a notification should get send to
    // more than 2 days in the future.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24) - 10,
      'notify_cron_next' => $now + (3600 * 50),
    ]);

    // Run cron and assert that no mails got sent.
    notify_cron();
    $this->assertCount(0, $this->getMails(), 'No emails are sent.');
  }

}
