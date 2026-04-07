<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Drupal\views\Views;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\user\UserInterface;
use Drupal\node\NodeInterface;

/**
 * Verify multi-day events spanning weekends render correctly in month view.
 *
 * This test ensures that multi-day events that span across weekends
 * display correctly in monthly calendar views with multi-column row style.
 *
 * @group calendar
 */
class MultiDayWeekendMonthViewTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'calendar',
    'datetime',
    'datetime_range',
    'views_ui',
    'node',
    'calendar_module_test',
  ];

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static array $testViews = [
    'multiday_month_calendar',
  ];

  /**
   * A user with admin privileges.
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['calendar_module_test']): void {
    parent::setUp($import_test_views, $modules);

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    // Create a user with the 'administer site configuration' permission.
    $this->adminUser = $this->drupalCreateUser([
      'administer calendar settings',
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);

    // Force site timezone to UTC for deterministic date rendering.
    $this->config('system.date')->set('timezone', [
      'default' => 'UTC',
      'user' => ['configurable' => FALSE],
    ])->save();

    // Ensure a content type and a daterange field exist for multi-day tests.
    $this->drupalCreateContentType(['type' => 'article']);

    // Create a datetime field to test.
    FieldStorageConfig::create([
      'field_name' => 'field_event_time',
      'entity_type' => 'node',
      'type' => 'daterange',
      'settings' => [
        'datetime_type' => 'datetime',
      ],
    ])->save();
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'field_event_time'),
      'bundle' => 'article',
      'label' => 'Event Time',
    ])->save();
  }

  /**
   * Test multi-day events spanning weekends display correctly.
   *
   * This test creates events that span from Thursday to Tuesday (crossing
   * a weekend) and verifies they display correctly in the monthly calendar
   * view with multi-column row style enabled.
   *
   * @dataProvider firstDayProvider
   */
  public function testMultiDayWeekendEvents(int $first_day_of_week): void {
    // Set the first day of the week in system configuration.
    $this->config('system.date')->set('first_day', $first_day_of_week)->save();

    // Create a multi-day event spanning Thursday to Tuesday (crossing weekend).
    // We'll use April 2025 where Thursday is April 3rd and Tuesday is
    // April 8th.
    $start_date = new \DateTimeImmutable('2025-04-03 09:00:00', new \DateTimeZone('UTC'));
    $end_date = new \DateTimeImmutable('2025-04-08 17:00:00', new \DateTimeZone('UTC'));

    $node = $this->createMultiDayEventNode($start_date, $end_date, 'Weekend Spanning Event');

    // Reuse the generic event assertions.
    $this->assertEventDisplaysCorrectly($node, $start_date, $end_date);

    // Recompute cells after navigation within the helper.
    $eventCells = $this->findEventCells($node);

    // Verify the event starts on the correct day (Thursday, April 3rd).
    $this->assertSame($start_date->format('Y-m-d'), $this->uniqueCellDates($eventCells)[0], 'Earliest cell matches event start date (Thursday).');

    // Verify the event ends on the correct day (Tuesday, April 8th).
    $this->assertContains($end_date->format('Y-m-d'), $this->uniqueCellDates($eventCells), 'Cells include the event end date (Tuesday).');

    // Verify the event spans across the weekend
    // (should include Saturday and Sunday).
    $this->assertContains('2025-04-05', $this->uniqueCellDates($eventCells), 'Event includes Saturday.');
    $this->assertContains('2025-04-06', $this->uniqueCellDates($eventCells), 'Event includes Sunday.');

    // Verify multi-day styling is applied correctly.
    $this->verifyMultiDayStyling($eventCells);

    // Clean up the node for the next iteration.
    $node->delete();
  }

  /**
   * Provides first-day-of-week values that exercise weekend spanning logic.
   *
   * @return int[][]
   *   Each tuple contains a single first-day-of-week value (0 = Sunday).
   */
  public static function firstDayProvider(): array {
    return [
      'sunday' => [0],
      'monday' => [1],
      'saturday' => [6],
    ];
  }

  /**
   * Verify multi-day events render when starting on different weekdays.
   *
   * @dataProvider multiDayEventProvider
   */
  public function testMultiDayEventsDifferentStartDays(string $start_date_string, string $end_date_string, string $event_title): void {
    $start_date = new \DateTimeImmutable($start_date_string . ' 09:00:00', new \DateTimeZone('UTC'));
    $end_date = new \DateTimeImmutable($end_date_string . ' 17:00:00', new \DateTimeZone('UTC'));

    $event_node = $this->createMultiDayEventNode($start_date, $end_date, $event_title);

    foreach (self::firstDayProvider() as [$first_day_of_week]) {
      $this->config('system.date')->set('first_day', $first_day_of_week)->save();
      $this->assertEventDisplaysCorrectly($event_node, $start_date, $end_date);
    }

    $event_node->delete();
  }

  /**
   * Provides differing multi-day spans across weekdays and weekends.
   *
   * @return string[][]
   *   Each tuple contains start date (Y-m-d), end date (Y-m-d),
   *   and event title.
   */
  public static function multiDayEventProvider(): array {
    return [
      'weekday_only' => ['2025-04-07', '2025-04-11', 'Monday to Friday Event'],
      'tuesday_to_sunday' => ['2025-04-08', '2025-04-13', 'Tuesday to Sunday Event'],
      'wednesday_to_monday' => ['2025-04-09', '2025-04-14', 'Wednesday to Monday Event'],
      'friday_to_tuesday' => ['2025-04-11', '2025-04-15', 'Friday to Tuesday Event'],
      'cross_month' => ['2025-04-30', '2025-05-02', 'Cross month Event'],
    ];
  }

  /**
   * Verify that an event displays correctly in the calendar.
   */
  private function assertEventDisplaysCorrectly(NodeInterface $node, \DateTimeImmutable $start_date, \DateTimeImmutable $end_date): void {
    // Navigate to the month view for the event's start month and confirm the
    // event is present.
    $view = Views::getView('multiday_month_calendar');
    $this->assertNotNull($view, 'Expected multi-day month view to be available.');
    $view->setDisplay('page_month');
    $view->setArguments([$start_date->format('Ym')]);
    $view->execute();

    $path = 'test-multiday/month/' . $start_date->format('Ym');
    $this->drupalGet($path);
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains($node->label());

    $eventCells = $this->findEventCells($node);

    $this->assertNotEmpty($eventCells, 'Event appears in calendar cells.');

    $unique = $this->uniqueCellDates($eventCells);

    // Compute the visible date range for the current month page.
    $month_start = (new \DateTimeImmutable(
      $start_date->format('Y-m-01'),
      new \DateTimeZone('UTC')
    ));
    $month_end = $month_start->modify('last day of this month');

    $visible_start = max(
      $start_date->format('Y-m-d'),
      $month_start->format('Y-m-d')
    );
    $visible_end = min(
      $end_date->format('Y-m-d'),
      $month_end->format('Y-m-d')
    );

    $expected_days = (
      (new \DateTimeImmutable($visible_start))
        ->diff(new \DateTimeImmutable($visible_end))
        ->days
    ) + 1;

    // The calendar may render an extra trailing cell (cutoff marker)
    // on the day after the visible end. Allow at most one extra.
    $this->assertGreaterThanOrEqual(
      $expected_days,
      count($unique),
      "Event renders at least {$expected_days} distinct days in this month."
    );
    $this->assertLessThanOrEqual(
      $expected_days + 1,
      count($unique),
      'Event renders no more than one extra day due to continuation markup.'
    );

    // Verify the first visible day matches exactly.
    $this->assertSame(
      $visible_start,
      $unique[0],
      'Earliest visible cell matches the expected start date for the month.'
    );

    // Verify the last day: either exactly the visible end or a single
    // extra trailing day (the cutoff cell on the next day).
    $last = $unique[count($unique) - 1];
    $day_after_visible_end = (new \DateTimeImmutable($visible_end))
      ->modify('+1 day')
      ->format('Y-m-d');
    $this->assertTrue(
      in_array($visible_end, $unique, TRUE) || $last === $day_after_visible_end,
      'Last visible day is the event end or the cutoff day after it.'
    );
  }

  /**
   * Verify multi-day styling is applied correctly.
   */
  private function verifyMultiDayStyling(array $eventCells): void {
    $totalCells = count($eventCells);
    $firstCell = $eventCells[0];
    $lastCell = $eventCells[$totalCells - 1];

    // Verify continuation markers are present.
    $this->assertNull($firstCell->find('css', '.continuation'), 'First day does not render a left continuation arrow.');
    $this->assertNotNull($firstCell->find('css', '.continues'), 'First day links forward to the next day.');
    $this->assertNull($lastCell->find('css', '.continues'), 'Final day does not render a continues marker.');
    $this->assertNotNull($lastCell->find('css', '.cutoff'), 'Final day renders the cutoff marker.');

    // Intermediate days should show continuation arrows to indicate ongoing
    // span.
    $continuationCells = [];
    foreach ($eventCells as $index => $cell) {
      if ($index === 0) {
        continue;
      }
      if ($cell->find('css', '.continuation') !== NULL) {
        $continuationCells[] = $cell;
      }
    }
    $this->assertNotEmpty($continuationCells, 'Continuation arrow appears on at least one subsequent day.');

    // Each cell in the span should carry the multi-day CSS class.
    foreach ($eventCells as $cell) {
      $this->assertStringContainsString('multi-day', $cell->getAttribute('class') ?? '',
        'Multi-day CSS class is applied to event cells.');
    }
  }

  /**
   * Helper to create a multi-day event node.
   */
  private function createMultiDayEventNode(\DateTimeImmutable $start, \DateTimeImmutable $end, string $title): NodeInterface {
    return $this->drupalCreateNode([
      'type' => 'article',
      'title' => $title,
      'created' => $start->getTimestamp(),
      'field_event_time' => [
        'value' => $start->format('Y-m-d\TH:i:s'),
        'end_value' => $end->format('Y-m-d\TH:i:s'),
      ],
    ]);
  }

  /**
   * Locate all month view cells that render the provided calendar event.
   */
  private function findEventCells(NodeInterface $node): array {
    $page = $this->getSession()->getPage();
    $prefix = sprintf('calendar.%d.field_event_time.0', $node->id());
    $xpath = sprintf("//div[contains(@class, '%s')]/ancestor::td[1]", $prefix);
    return $page->findAll('xpath', $xpath);
  }

  /**
   * Extract sorted unique YYYY-MM-DD dates from month view cells.
   *
   * @param array $cells
   *   Cell elements containing a data-date attribute.
   *
   * @return string[]
   *   Sorted unique dates in YYYY-MM-DD format.
   */
  private function uniqueCellDates(array $cells): array {
    $dates = array_map(static function ($cell) {
      return $cell->getAttribute('data-date');
    }, $cells);
    $dates = array_values(array_unique($dates));
    sort($dates);
    return $dates;
  }

}
