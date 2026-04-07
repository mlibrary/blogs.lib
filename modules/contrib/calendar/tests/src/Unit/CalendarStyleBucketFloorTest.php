<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Unit;

use Drupal\calendar\CalendarStyleInfo;
use Drupal\calendar\Plugin\views\style\Calendar;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for bucketFloor normalization.
 *
 * @group calendar
 * @covers \Drupal\calendar\Plugin\views\style\Calendar::bucketFloor
 */
class CalendarStyleBucketFloorTest extends UnitTestCase {

  /**
   * The calendar style plugin under test.
   */
  private Calendar $calendarStyle;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->calendarStyle = new Calendar(
      [],
      'calendar',
      [],
      $this->createMock(DateFormatter::class),
      $this->createMock(MessengerInterface::class),
      $this->createMock(TimeInterface::class),
      $this->createMock(ConfigFactoryInterface::class)
    );

    $style_info = new CalendarStyleInfo();
    $style_info->setGroupByTimes([
      '00:00:00',
      '00:30:00',
      '01:00:00',
      '01:30:00',
      '02:00:00',
    ]);

    $reflection = new \ReflectionClass($this->calendarStyle);
    $property = $reflection->getProperty('styleInfo');
    $property->setAccessible(TRUE);
    $property->setValue($this->calendarStyle, $style_info);
  }

  /**
   * Ensures DST fall-back times retain their intended bucket.
   */
  public function testBucketFloorPreservesDstWallClock(): void {
    $reflection = new \ReflectionClass($this->calendarStyle);
    $method = $reflection->getMethod('bucketFloor');
    $method->setAccessible(TRUE);

    $tz = new \DateTimeZone('America/New_York');
    $first_occurrence = (new \DateTimeImmutable('2024-11-03 05:30:00', new \DateTimeZone('UTC')))->setTimezone($tz);
    $second_occurrence = (new \DateTimeImmutable('2024-11-03 06:30:00', new \DateTimeZone('UTC')))->setTimezone($tz);

    $this->assertSame('01:30:00', $method->invoke($this->calendarStyle, $first_occurrence));
    $this->assertSame('01:30:00', $method->invoke($this->calendarStyle, $second_occurrence));
  }

}
