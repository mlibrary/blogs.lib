<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\calendar\DateArgumentWrapper;
use Drupal\calendar\Plugin\views\argument\TimeStampYearWeekDate;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\argument\Date;

/**
 * Verifies calendar week arguments expose ISO week formats.
 *
 * @group calendar
 * @covers \Drupal\calendar\Plugin\views\argument\TimeStampYearWeekDate
 * @covers \Drupal\calendar\Plugin\views\argument\DatetimeYearWeekDate
 */
class WeekArgumentFormatTest extends UnitTestCase {

  /**
   * Week argument plugins expose ISO week format via the wrapper.
   */
  public function testWeekArgumentsExposeIsoFormat(): void {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $date_formatter = $this->createMock(DateFormatterInterface::class);
    $time = $this->createMock(TimeInterface::class);

    $timestampArgument = new TimeStampYearWeekDate([], 'date_year_week', [], $route_match, $date_formatter, $time);
    $this->assertInstanceOf(Date::class, $timestampArgument);
    $wrapper = new DateArgumentWrapper($timestampArgument);
    $this->assertSame('oW', $wrapper->getArgFormat());

    $datetimeDefaults = (new \ReflectionClass('\Drupal\calendar\Plugin\views\argument\DatetimeYearWeekDate'))->getDefaultProperties();
    $this->assertArrayHasKey('argFormat', $datetimeDefaults);
    $this->assertSame('oW', $datetimeDefaults['argFormat']);
  }

}
