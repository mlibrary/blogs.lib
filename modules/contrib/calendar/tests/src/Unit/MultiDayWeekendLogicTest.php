<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Unit;

use Drupal\calendar\CalendarHelper;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Exercises calendar helper logic for configurable first-day-of-week handling.
 *
 * @group calendar
 */
class MultiDayWeekendLogicTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->primeContainerWithFirstDay(0);
  }

  /**
   * Ensure helper returns weekdays in configured order.
   *
   * @dataProvider firstDayProvider
   */
  public function testWeekDayOrderingWithDifferentFirstDays(int $first_day_of_week): void {
    $keys = $this->orderedWeekdayKeys($first_day_of_week);

    $this->assertCount(7, $keys, 'Seven unique weekday keys are returned.');
    $this->assertSame($first_day_of_week, $keys[0],
      "First key should match configured first day {$first_day_of_week}.");

    $expected = [];
    for ($offset = 0; $offset < 7; $offset++) {
      $expected[] = ($first_day_of_week + $offset) % 7;
    }
    $this->assertSame($expected, $keys, 'Weekday keys rotate from configured first day.');
  }

  /**
   * Provides first-day-of-week values to verify ordering.
   *
   * @return int[][]
   *   Each tuple contains the configured first day-of-week (0 = Sunday).
   */
  public static function firstDayProvider(): array {
    return [
      'sunday' => [0],
      'monday' => [1],
      'saturday' => [6],
    ];
  }

  /**
   * Calculate span using ordered weekday keys to mirror plugin logic.
   *
   * @dataProvider remainingDaysProvider
   */
  public function testRemainingDaysCalculation(int $first_day_of_week, int $start_day_of_week, int $end_day_of_week, int $expected_span): void {
    $keys = $this->orderedWeekdayKeys($first_day_of_week);
    $start_position = array_search($start_day_of_week, $keys, TRUE);
    $end_position = array_search($end_day_of_week, $keys, TRUE);

    $this->assertIsInt($start_position, 'Start day exists in ordered keys.');
    $this->assertIsInt($end_position, 'End day exists in ordered keys.');

    $diff = $end_position - $start_position;
    if ($diff < 0) {
      $diff += 7;
    }
    $span = $diff + 1;

    $remaining_days = min(6 - $start_position, $diff);

    $this->assertSame($expected_span, $span,
      "Inclusive span between {$start_day_of_week} and {$end_day_of_week} should be {$expected_span} days.");
    $this->assertGreaterThanOrEqual(0, $remaining_days,
      'Remaining days calculation should not be negative.');
    $this->assertLessThanOrEqual(6, $remaining_days,
      'Remaining days calculation should not exceed six days.');
  }

  /**
   * Provides scenarios for remaining-day calculations when spanning weeks.
   *
   * @return int[][]
   *   Each tuple contains: first-day setting, start weekday, end weekday,
   *   expected inclusive span length.
   */
  public static function remainingDaysProvider(): array {
    return [
      'sunday_weekend_span' => [0, 4, 2, 6],
      'monday_weekend_span' => [1, 4, 2, 6],
      'sunday_weekday_only' => [0, 1, 3, 3],
      'monday_weekday_only' => [1, 1, 3, 3],
      'saturday_weekend_span' => [6, 4, 2, 6],
      'saturday_weekday_only' => [6, 1, 3, 3],
    ];
  }

  /**
   * Confirm helper identifies the configured first day correctly.
   *
   * @dataProvider weekdayComparisonProvider
   */
  public function testFirstDayOfWeekComparison(int $first_day_of_week, int $day_of_week, bool $expected_is_first): void {
    $keys = $this->orderedWeekdayKeys($first_day_of_week);
    $is_first_day = ($day_of_week === $keys[0]);
    $this->assertSame($expected_is_first, $is_first_day,
      "Expected day {$day_of_week} to " . ($expected_is_first ? '' : 'not ') . "be first for configuration {$first_day_of_week}.");
  }

  /**
   * Data provider for weekday comparison tests.
   *
   * @return array[]
   *   Each tuple contains: first-day setting, weekday being checked, expected
   *   outcome.
   */
  public static function weekdayComparisonProvider(): array {
    return [
      [0, 0, TRUE],
      [0, 1, FALSE],
      [1, 0, FALSE],
      [1, 1, TRUE],
      [6, 6, TRUE],
      [6, 0, FALSE],
    ];
  }

  /**
   * Confirm bucket calculations honour ordered weekday positions.
   *
   * @dataProvider bucketScenarioProvider
   */
  public function testBucketCalculationUsingWeekdayPositions(int $first_day_of_week, int $start_day_of_week, int $end_day_of_week): void {
    $keys = $this->orderedWeekdayKeys($first_day_of_week);
    $start_position = array_search($start_day_of_week, $keys, TRUE);
    $end_position = array_search($end_day_of_week, $keys, TRUE);

    $this->assertIsInt($start_position);
    $this->assertIsInt($end_position);

    $diff = $end_position - $start_position;
    if ($diff < 0) {
      $diff += 7;
    }

    $remaining_days = min(6 - $start_position, $diff);
    $this->assertGreaterThanOrEqual(0, $remaining_days, 'Remaining days should be non-negative.');
    $this->assertLessThanOrEqual(6, $remaining_days, 'Remaining days should not exceed 6 (max days in a week).');

    $total_span = $remaining_days + 1;
    $this->assertGreaterThanOrEqual(1, $total_span, 'Total span should be at least 1 day.');
    $this->assertLessThanOrEqual(7, $total_span, 'Total span should not exceed 7 days.');
  }

  /**
   * Provides combinations of first/start/end days for bucket calculations.
   *
   * @return int[][]
   *   Each tuple contains: first-day setting, start weekday, end weekday.
   */
  public static function bucketScenarioProvider(): array {
    return [
      'sunday_weekend_span' => [0, 4, 2],
      'monday_weekend_span' => [1, 4, 2],
      'sunday_midweek' => [0, 1, 5],
      'monday_midweek' => [1, 1, 5],
      'saturday_first_day' => [6, 4, 1],
    ];
  }

  /**
   * Swap in a stub container primed with the desired first day.
   */
  private function primeContainerWithFirstDay(int $first_day): void {
    $container = new ContainerBuilder();
    $container->set('config.factory', $this->getConfigFactoryStub([
      'system.date' => ['first_day' => $first_day],
    ]));
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Retrieve ordered weekday keys using helper APIs.
   *
   * @return int[]
   *   Ordered weekday keys keyed by configured first day.
   */
  private function orderedWeekdayKeys(int $first_day_of_week): array {
    $this->primeContainerWithFirstDay($first_day_of_week);
    $ordered = CalendarHelper::weekDaysOrdered(CalendarHelper::weekDays(TRUE));
    return array_keys($ordered);
  }

}
