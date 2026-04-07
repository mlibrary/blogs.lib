<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Unit;

use Drupal\calendar\CalendarHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests week-number calculation for different first-day configurations.
 *
 * @group calendar
 * @covers \Drupal\calendar\CalendarHelper::dateWeek
 */
class CalendarHelperWeekNumberTest extends UnitTestCase {

  /**
   * Configure the first day of the week setting for the test run.
   */
  protected function setFirstDay(int $first_day): void {
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'system.date' => ['first_day' => $first_day],
    ]));
    \Drupal::setContainer($container);
  }

  /**
   * Ensures ISO week numbers are returned when Monday is the first day.
   */
  public function testIsoWeeksWhenMondayIsFirstDay(): void {
    $this->setFirstDay(1);

    $this->assertSame(1, CalendarHelper::dateWeek('2021-01-04'));
    $this->assertSame(53, CalendarHelper::dateWeek('2020-12-31'));
    $this->assertSame(53, CalendarHelper::dateWeek('2016-01-01'));
  }

  /**
   * Verifies existing behavior remains for Sunday-start calendars.
   */
  public function testSundayFirstDayStillUsesCalendarWeeks(): void {
    $this->setFirstDay(0);

    $this->assertSame(2, CalendarHelper::dateWeek('2021-01-04'));
  }

}
