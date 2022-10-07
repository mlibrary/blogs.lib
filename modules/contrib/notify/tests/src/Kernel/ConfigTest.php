<?php

namespace Drupal\Tests\notify\Kernel;

use Drupal\notify\NotifyInterface;

/**
 * Tests behavior against using config.
 *
 * @group notify
 */
class ConfigTest extends NotifyKernelTestBase {

  /**
   * An admin user that may administer nodes.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $admin;

  /**
   * A regular user that receives notifications about new content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * A regular user that receives notifications about new content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account2;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Subscribe new users to articles by default.
    $this->setNotifySettings([
      'notify_nodetypes' => [
        'article' => 1,
      ],
    ]);

    // Create an admin user that can receive notifications.
    $this->admin = $this->createNotifyUser([
      'administer nodes',
    ]);

    $this->account = $this->createNotifyUser();
    $this->account2 = $this->createNotifyUser();

    // Configure to not send notifications about updated content.
    $this->setNotifySettings([
      'notify_include_updates' => 0,
    ]);
  }

  /**
   * Tests that with certain settings no notifications are send.
   */
  public function testCronNever() {
    // Configure the module to never send notifications.
    $this->setNotifySettings([
      'notify_period' => NotifyInterface::PERIOD_NEVER,
    ]);

    // Create a node.
    $this->createNode();

    // Trigger notification run and assert that no mails were sent.
    notify_cron();
    $this->assertCount(0, $this->getMails(), 'No emails are sent.');
  }

  /**
   * Tests that no notifications are send for old content.
   *
   * The time notifications were sent last is set to yesterday. The time the
   * last node was created is set to yesterday minus one second.
   */
  public function testNoNotificationsForOldArticle() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Set the last time a notification was sent to one day ago.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
    ]);

    // Create a node that was created more than a day ago.
    $this->createNode([
      'created' => $now - (3600 * 24) - 1,
    ]);

    // Trigger notification run and assert that no mails were sent.
    notify_cron();
    $this->assertCount(0, $this->getMails(), 'No emails are sent.');
  }

  /**
   * Tests that notifications about unpublished nodes are only send to admins.
   */
  public function testWithUnpublishedArticles() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Set the notify period to always.
    $this->setNotifySettings([
      'notify_period' => NotifyInterface::PERIOD_ALWAYS,
    ]);

    // Set the last time a notification was sent to one day ago and the next
    // time to tomorrow.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
      'notify_cron_next' => $now + (3600 * 24),
    ]);

    // Create three unpublished nodes with different authors.
    $this->createNode([
      'status' => 0,
      'uid' => $this->admin->id(),
    ]);
    $this->createNode([
      'status' => 0,
      'uid' => $this->account->id(),
    ]);
    $this->createNode([
      'status' => 0,
    ]);

    notify_cron();
    $this->assertCount(1, $this->getMails(), 'One email is sent.');
  }

  /**
   * Tests that notifications about new nodes are send.
   */
  public function testWithNewArticles() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Set the notify period to always.
    $this->setNotifySettings([
      'notify_period' => NotifyInterface::PERIOD_ALWAYS,
    ]);

    // Set the last time a notification was sent to one day ago and the next
    // time to tomorrow.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
      'notify_cron_next' => $now + (3600 * 24),
    ]);

    // Create three nodes with different authors.
    $this->createNode([
      'uid' => $this->admin->id(),
    ]);
    $this->createNode([
      'uid' => $this->account->id(),
    ]);
    $this->createNode();

    // Run cron and assert that three mails are sent.
    notify_cron();
    $this->assertCount(3, $this->getMails(), 'Three emails are sent.');
  }

  /**
   * Tests that notifications for only new content are send.
   */
  public function testWithOldAndNewArticles() {
    // Get the current time.
    $now = \Drupal::time()->getRequestTime();

    // Set the notify period to 10 minutes.
    $this->setNotifySettings([
      'notify_period' => 600,
    ]);

    // Set the last time a notification was sent to one day ago.
    $this->setNotifyStates([
      'notify_send_last' => $now - (3600 * 24),
    ]);

    // Create one node older than a day and two that are about five hours old.
    $this->createNode([
      'created' => $now - (3600 * 24) - 1,
    ]);
    $this->createNode([
      'created' => $now - (3600 * 5),
    ]);
    $this->createNode([
      'created' => $now - (3600 * 5),
    ]);

    // Run cron and assert that three mails are sent.
    notify_cron();
    $this->assertCount(3, $this->getMails(), 'Three emails are sent.');
  }

  /**
   * Tests notifications aren't send again when importing config.
   */
  public function testConfigImport() {
    // Export the notification config as it is now.
    $export = $this->container->get('config.factory')
      ->get('notify.settings')
      ->getRawData();

    // Create a new node.
    $this->createNode();

    // Trigger notification run.
    notify_cron();

    // Assert that the user got a notification.
    $this->assertMail('id', 'notify_notice');
    $this->assertMail('to', $this->account2->mail->value);
    $this->assertCount(3, $this->getMails());

    // Trigger notification run again and assert no new mails were sent.
    notify_cron();
    $this->assertCount(3, $this->getMails());

    // Import the config.
    $this->container->get('config.factory')
      ->getEditable('notify.settings')
      ->setData($export)
      ->save();

    // Trigger notification run again and assert that no new mails were sent.
    notify_cron();
    $this->assertCount(3, $this->getMails(), 'After config import no new mails are sent.');
  }

}
