<?php

namespace Drupal\Tests\notify\Kernel;

/**
 * Tests for sending notifications in batches.
 *
 * @group notify
 */
class PeriodsTest extends NotifyKernelTestBase {

  /**
   * Tests sending notifications with various period settings.
   *
   * @param int $expected
   *   The expected number of mails sent.
   * @param array $settings
   *   The notify settings.
   * @param array $state
   *   The notify status variables.
   *
   * @dataProvider periodsDataProvider
   */
  public function testPeriods(int $expected, array $settings = [], array $states = []) {
    $this->setNotifySettings($settings);
    $this->setNotifyStates($states);

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

    // Create a few nodes.
    $this->createNode();
    $this->createNode();
    $this->createNode();

    // Run cron and assert number of sent mails.
    notify_cron();
    $this->assertCount($expected, $this->getMails());
  }

  /**
   * Data provider for ::testPeriods().
   */
  public function periodsDataProvider() {
    return [
      'send-always-and-never-sent-before' => [
        'expected' => 3,
        'settings' => [
          'notify_period' => 0,
        ],
        'states' => [],
      ],
      'send-always-and-sent-yesterday-last' => [
        'expected' => 3,
        'settings' => [
          'notify_period' => 0,
        ],
        'states' => [
          'notify_send_last' => time() - (3600 * 24),
        ],
      ],
      'send-every-hour-and-never-sent-before' => [
        'expected' => 3,
        'settings' => [
          'notify_period' => 3600,
        ],
        'states' => [],
      ],
    ];
  }

}
