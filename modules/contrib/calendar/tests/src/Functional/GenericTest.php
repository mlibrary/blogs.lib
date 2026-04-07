<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\calendar\DateArgumentWrapper;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeInterface;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\user\UserInterface;
use Drupal\views\Plugin\views\argument\Date as ViewsDateArgument;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Test description.
 *
 * @group calendar
 */
class GenericTest extends ViewTestBase {

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
    'content_authored_on_calendar',
    'overlap_range_calendar',
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

    // Ensure a content type and a daterange field exist for overlap tests.
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
   * Tests calendar admin settings page.
   */
  public function testCalendarAdminPage(): void {
    $this->drupalGet('admin/config/date/calendar');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->buttonExists('Save configuration')->submit();
    $session->pageTextContains('The configuration options have been saved');
  }

  /**
   * Tests if the 'Add view from template' link exists.
   */
  public function testAddViewFromTemplateLinkExists(): void {
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->statusCodeEquals(200);

    // Retrieve the link and verify it points to the expected URL.
    $session = $this->assertSession();
    $session->linkExists('Add view from template');
  }

  /**
   * Test an event appears in the right cell in the calendar.
   */
  public function testEventAppearsOnCorrectDayInCalendar(): void {
    // Set the date for the node to the first of a fixed date.
    $date = new \DateTime('2025-04-01 12:00:00');

    // Create a node with the specified date.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'created' => $date->getTimestamp(),
    ]);

    $view = Views::getView('content_authored_on_calendar');
    $view->setDisplay('page_month');
    $view->execute();

    // Navigate to the calendar page.
    $output = $this->drupalGet($view->getUrl([
      $date->format('Ym'),
    ]));

    // Check the header is on the page.
    $this->assertStringContainsString($date->format('F Y'), $output);
    // Ensure the node title is on the page, somewhere.
    $this->assertSession()->pageTextContains($node->label());

    // Locate the calendar cell for the specified date.
    $day_of_month = $date->format('j');
    $xpath = "//tr[contains(@class, 'single-day')]//td[@data-day-of-month='{$day_of_month}']";
    $calendar_cell = $this->getSession()->getPage()->find('xpath', $xpath);

    // Assert that the calendar cell exists.
    $this->assertNotNull($calendar_cell, "The calendar cell for day {$day_of_month} exists.");

    // Check if the node's title is present in the calendar cell.
    $this->assertStringContainsString(
      $node->label(),
      $calendar_cell->getHtml(),
      "The node titled '{$node->label()}' appears in the calendar cell for day {$day_of_month}."
    );
  }

  /**
   * Day view renders and shows events.
   */
  public function testDayViewRenders(): void {
    $date = new \DateTime('2025-04-01 12:00:00');

    // Ensure a content type and a node exist on the target date.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'created' => $date->getTimestamp(),
    ]);

    $view = Views::getView('content_authored_on_calendar');
    $view->setDisplay('page_day');
    $this->drupalGet($view->getUrl([
      $date->format('Ymd'),
    ]));

    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    // Basic structure rendered.
    $session->elementExists('css', '.day-view');
    $session->elementExists('css', '.calendar-agenda-hour');
    // Event is present.
    $session->pageTextContains($node->label());
  }

  /**
   * Week view renders and shows events.
   */
  public function testWeekViewRenders(): void {
    $date = new \DateTime('2025-04-01 12:00:00');

    // Ensure a content type and a node exist on the target date.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'created' => $date->getTimestamp(),
    ]);

    $view = Views::getView('content_authored_on_calendar');
    $view->setDisplay('page_week');

    $weekArgument = $this->formatCalendarArgument($view, 'created_year_week', $date);
    $this->drupalGet($view->getUrl([$weekArgument]));

    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    // Basic structure rendered.
    $session->elementExists('css', '.week-view');
    $session->elementExists('css', '.calendar-agenda-hour');
    // Event is present.
    $session->pageTextContains($node->label());
  }

  /**
   * Week contextual filters use ISO week (oW) formatting.
   */
  public function testWeekArgumentUsesIsoWeekYear(): void {
    $date = new \DateTime('2024-12-31 12:00:00');

    $view = Views::getView('content_authored_on_calendar');
    $view->setDisplay('page_week');

    $display = $view->display_handler;
    $this->assertNotNull($display, 'Display handler is available for week view.');

    $argument = $display->getHandler('argument', 'created_year_week');
    $this->assertNotNull($argument, 'Week argument handler is available.');
    $this->assertInstanceOf(ViewsDateArgument::class, $argument);

    $wrapper = new DateArgumentWrapper($argument);
    $this->assertSame('oW', $wrapper->getArgFormat(), 'Week argument uses ISO week-year format.');

    $formatted = $this->formatCalendarArgument($view, 'created_year_week', $date);
    $this->assertSame($date->format('oW'), $formatted);
    $this->assertNotSame($date->format('YW'), $formatted);
  }

  /**
   * Format a calendar view argument for the provided date.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view instance with an active display.
   * @param string $argumentId
   *   The identifier of the argument to format.
   * @param \DateTimeInterface $date
   *   The date used to build the argument value.
   *
   * @return string
   *   The formatted argument string.
   */
  private function formatCalendarArgument(ViewExecutable $view, string $argumentId, \DateTimeInterface $date): string {
    $display = $view->display_handler;
    $this->assertNotNull($display, sprintf('Display handler is available when formatting %s.', $argumentId));
    $argument = $display->getHandler('argument', $argumentId);
    $this->assertNotNull($argument, sprintf('Argument handler %s is available.', $argumentId));
    $this->assertInstanceOf(ViewsDateArgument::class, $argument, 'Calendar argument handler uses a date plugin.');

    $wrapper = new DateArgumentWrapper($argument);
    return $date->format($wrapper->getArgFormat());
  }

  /**
   * Functional: overlapping events render with non-zero indents.
   */
  public function testOverlappingIndentsRendered(): void {
    // Create three overlapping events on the same day.
    $date = new \DateTimeImmutable('2025-04-01', new \DateTimeZone('UTC'));
    $a = $this->createEventNode($date->setTime(9, 0), $date->setTime(10, 0));
    $b = $this->createEventNode($date->setTime(9, 30), $date->setTime(9, 45));
    $c = $this->createEventNode($date->setTime(9, 40), $date->setTime(9, 50));

    // Visit the day page for the date and assert indent classes are present.
    $this->drupalGet('test-overlap/day/' . $date->format('Ymd'));
    $session = $this->assertSession();
    $session->statusCodeEquals(200);

    // Use the created nodes to assert which event received which indent.
    $this->assertIndentForNode($a, 0);
    $this->assertIndentForNode($b, 3);
    $this->assertIndentForNode($c, 7);
  }

  /**
   * Overlaps where a trailing event begins mid-slot keep the lead event wide.
   */
  public function testLeadOverlapResetsMaxDepth(): void {
    $day = new \DateTimeImmutable('2025-04-01 09:00:00', new \DateTimeZone('UTC'));
    $lead = $this->createEventNode($day, $day->setTime(11, 0));
    $this->createEventNode($day->setTime(10, 0), $day->setTime(10, 45));

    $this->drupalGet('test-overlap/day/' . $day->format('Ymd'));
    $wrapper = $this->findEventWrapper($lead);
    $this->assertNotNull($wrapper, 'Lead overlap event wrapper should exist.');
    $class = $wrapper->getAttribute('class') ?? '';
    $this->assertStringContainsString('md_0', $class, 'Lead event keeps max depth classification.');
    $this->assertStringContainsString('i_0', $class, 'Lead event remains in the first indent column.');
  }

  /**
   * Events starting between bucket floors receive the expected offset class.
   */
  public function testBucketFloorCalculatesQuarterHourOffset(): void {
    $day = new \DateTimeImmutable('2025-04-02 00:00:00', new \DateTimeZone('UTC'));
    $event = $this->createEventNode($day->setTime(9, 45), $day->setTime(10, 15));

    $this->drupalGet('test-overlap/day/' . $day->format('Ymd'));
    $wrapper = $this->findEventWrapper($event);
    $this->assertNotNull($wrapper, 'Offset event wrapper should exist.');
    $class = $wrapper->getAttribute('class') ?? '';
    $this->assertStringContainsString('o_3', $class, 'Offset class reflects quarter-hour start time.');
  }

  /**
   * Month view renders continuation arrows for multi-week events.
   */
  public function testMonthViewContinuationArrows(): void {
    $start = new \DateTimeImmutable('2025-04-04 09:00:00', new \DateTimeZone('UTC'));
    $end = new \DateTimeImmutable('2025-04-09 17:00:00', new \DateTimeZone('UTC'));
    $node = $this->createEventNode($start, $end);

    $view = Views::getView('multiday_month_calendar');
    $this->assertNotNull($view, 'Multi-day month view is available.');
    $view->setDisplay('page_month');
    $view->setArguments([$start->format('Ym')]);
    $view->execute();
    $this->assertGreaterThan(0, count($view->result), 'Month view returns at least one event.');
    $matchingRows = array_filter($view->result, static function ($row) use ($node) {
      return isset($row->_entity) && (int) $row->_entity->id() === (int) $node->id();
    });
    $this->assertNotEmpty($matchingRows, 'View results include the created event.');
    $path = 'test-multiday/month/' . $start->format('Ym');
    $this->drupalGet($path);
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains($node->label());
    $eventCells = $this->findEventCells($node);
    $this->assertNotEmpty($eventCells, 'Event appears in one or more calendar cells.');
    $cellDates = array_map(static function ($cell) {
      return [$cell->getAttribute('data-date'), $cell->getAttribute('data-day-of-month')];
    }, $eventCells);
    $this->assertGreaterThanOrEqual(2, count($eventCells), 'Event spans multiple day cells.');
    $this->assertEquals($start->format('Y-m-d'), $cellDates[0][0], 'First cell matches event start date.');
    $this->assertContains($end->format('Y-m-d'), array_column($cellDates, 0), 'Cells include the event end date.');

    $totalCells = count($eventCells);
    $firstCell = $eventCells[0];
    $lastCell = $eventCells[$totalCells - 1];
    $this->assertNull($firstCell->find('css', '.continuation'), 'First day does not render a left continuation arrow.');
    $this->assertNotNull($firstCell->find('css', '.continues'), 'First day links forward to the next day.');
    $this->assertNull($lastCell->find('css', '.continues'), 'Final day does not render a continues marker.');
    $this->assertNotNull($lastCell->find('css', '.cutoff'), 'Final day renders the cutoff marker.');

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
  }

  /**
   * Assert that a rendered calendar item containing a title has a given indent.
   */
  private function assertIndentForTitle(string $title, int $expectedIndent): void {
    // Find the nearest ancestor with an i_* class and inspect its class list.
    $xpath = sprintf(
      "(//*[contains(normalize-space(.), '%s')]/ancestor::div[contains(@class, 'i_')])[1]",
      $title,
    );
    $element = $this->getSession()->getPage()->find('xpath', $xpath);
    $this->assertNotNull($element, sprintf('Could not find calendar item wrapper for title %s', $title));
    $class = $element->getAttribute('class') ?? '';
    $this->assertNotSame('', $class, 'Wrapper has class attribute');
    $this->assertMatchesRegularExpression('/\\bi_(\\d+)\\b/', $class, sprintf('Class contains an i_* token: %s', $class));
    if (preg_match('/\\bi_(\\d+)\\b/', $class, $m)) {
      $actual = (int) $m[1];
      $this->assertSame($expectedIndent, $actual, sprintf('Expected indent i_%d, got %s (classes: %s)', $expectedIndent, $class, $class));
    }
  }

  /**
   * Helper to create an article with the provided start/end date and times.
   */
  private function createEventNode(\DateTimeInterface $start, \DateTimeInterface $end): NodeInterface {
    return $this->drupalCreateNode([
      'type' => 'article',
      'title' => $start->format('H:i:s') . '-' . $end->format('H:i:s'),
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
   * Assert indent for a specific node using its calendar date_id wrapper.
   */
  private function assertIndentForNode(NodeInterface $node, int $expectedIndent): void {
    $element = $this->findEventWrapper($node);
    if (!$element) {
      $this->assertIndentForTitle($node->label(), $expectedIndent);
      return;
    }
    $class = $element->getAttribute('class') ?? '';
    $this->assertMatchesRegularExpression('/\\bi_(\\d+)\\b/', $class, sprintf('Class contains an i_* token: %s', $class));
    if (preg_match('/\\bi_(\\d+)\\b/', $class, $m)) {
      $actual = (int) $m[1];
      $this->assertSame($expectedIndent, $actual, sprintf('Expected indent i_%d, got classes: %s', $expectedIndent, $class));
    }
  }

  /**
   * Locate the rendered wrapper div for a calendar node.
   */
  private function findEventWrapper(NodeInterface $node): ?NodeElement {
    $page = $this->getSession()->getPage();
    $classPrefix = sprintf('calendar.%d.field_event_time.0', $node->id());
    $xpath = sprintf("//div[contains(@class, '%s')]/ancestor::div[contains(@class, 'd_')][1]", $classPrefix);
    $element = $page->find('xpath', $xpath);
    if (!$element) {
      $hrefXpath = sprintf("//a[contains(@href, '/node/%d')]/ancestor::div[contains(@class, 'd_')][1]", $node->id());
      $element = $page->find('xpath', $hrefXpath);
    }
    return $element;
  }

}
