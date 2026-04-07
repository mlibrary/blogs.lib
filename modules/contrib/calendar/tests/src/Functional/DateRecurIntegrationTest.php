<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Views;

/**
 * Test date_recur integration with calendar.
 *
 * @group calendar
 */
class DateRecurIntegrationTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'calendar',
    'datetime',
    'datetime_range',
    'date_recur',
    'views_ui',
    'node',
    'field',
    'calendar_module_test',
  ];

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static array $testViews = ['date_recur_calendar'];

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Creates the recurring event node used across tests.
   */
  protected function createRecurringEvent(array $overrides = []): NodeInterface {
    $default = [
      'type' => 'event',
      'title' => 'Weekly Tuesday Meeting',
      'field_event_date' => [
        'value' => '2025-02-04T08:00:00',
        'end_value' => '2025-02-04T09:00:00',
        'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TU;UNTIL=20250410T065959Z;WKST=SU',
        'timezone' => 'UTC',
      ],
    ];
    $values = array_replace_recursive($default, $overrides);
    return $this->drupalCreateNode($values);
  }

  /**
   * Assert that a calendar cell for a given day contains text.
   */
  private function assertCellHas(int $dayOfMonth, string $text): void {
    $this->assertSession()->elementContains(
      'xpath',
      "//tr[contains(@class,'single-day')]//td[@data-day-of-month='{$dayOfMonth}']",
      $text
    );
  }

  /**
   * Assert that a calendar cell for a given day does NOT contain text.
   */
  private function assertCellNotHas(int $dayOfMonth, string $text): void {
    $cell = $this->getSession()->getPage()->find(
      'xpath',
      "//tr[contains(@class,'single-day')]//td[@data-day-of-month='{$dayOfMonth}']"
    );
    $this->assertNotNull($cell, "The calendar cell for day {$dayOfMonth} exists.");
    $this->assertStringNotContainsString($text, $cell->getHtml());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['calendar_module_test']): void {
    parent::setUp($import_test_views, $modules);

    // Initialize services.
    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create content type with date_recur field.
    $this->createEventContentType();
  }

  /**
   * Creates an event content type with a date_recur field.
   */
  protected function createEventContentType(): void {
    $node_type = NodeType::create([
      'type' => 'event',
      'name' => 'Event',
    ]);
    $node_type->save();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_event_date',
      'entity_type' => 'node',
      'type' => 'date_recur',
      'settings' => [
        'datetime_type' => 'datetime',
      ],
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'event',
      'label' => 'Event Date',
      'required' => TRUE,
    ]);
    $field->save();
  }

  /**
   * Data provider for occurrence ranges.
   *
   * @return array[]
   *   Each set: [start, end, expectedDates].
   */
  public static function occurrenceRangeProvider(): array {
    return [
      'april 2025' => [
        '2025-04-01T00:00:00',
        '2025-04-30T23:59:59',
        ['2025-04-01', '2025-04-08'],
      ],
      'february 2025' => [
        '2025-02-01T00:00:00',
        '2025-02-28T23:59:59',
        ['2025-02-04', '2025-02-11', '2025-02-18', '2025-02-25'],
      ],
      'may 2025 (after until)' => [
        '2025-05-01T00:00:00',
        '2025-05-31T23:59:59',
        [],
      ],
    ];
  }

  /**
   * Test occurrences in range.
   *
   * @dataProvider occurrenceRangeProvider
   */
  public function testOccurrencesInRange(string $start, string $end, array $expectedDates): void {
    $node = $this->createRecurringEvent();

    $field_item = $node->get('field_event_date')->first();
    $this->assertInstanceOf(DateRecurItem::class, $field_item, 'Date recur field item exists.');
    $this->assertSame('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU;UNTIL=20250410T065959Z;WKST=SU', $field_item->rrule);

    $helper = $field_item->getHelper();
    $this->assertNotNull($helper, 'Date recur helper is available.');

    $startDt = new \DateTimeImmutable($start, new \DateTimeZone('UTC'));
    $endDt = new \DateTimeImmutable($end, new \DateTimeZone('UTC'));
    $occurrences = $helper->getOccurrences($startDt, $endDt, NULL);

    $expectedCount = count($expectedDates);
    $this->assertCount($expectedCount, $occurrences, "Expected {$expectedCount} occurrences in range {$start} - {$end}.");

    $dates = array_map(static fn($o) => $o->getStart()->format('Y-m-d'), $occurrences);
    foreach ($expectedDates as $expectedDate) {
      $this->assertContains($expectedDate, $dates, "{$expectedDate} occurrence exists.");
    }
  }

  /**
   * Test recurring events appear in correct calendar cells for April 2025.
   */
  public function testRecurringEventsAppearInCalendarCellsApril2025(): void {
    // Create a node with the specified RRULE.
    $node = $this->createRecurringEvent();

    // Get the date_recur_calendar view and set it to month display.
    $view = Views::getView('date_recur_calendar');
    $view->setDisplay('page_month');
    $view->execute();

    // Navigate to the April 2025 calendar page.
    $april_date = new \DateTimeImmutable('2025-04-01');
    $output = $this->drupalGet($view->getUrl([
      $april_date->format('Ym'),
    ]));

    // Check the header contains April 2025.
    $this->assertStringContainsString($april_date->format('F Y'), $output);

    // April 1st should contain the title.
    $this->assertCellHas(1, $node->label());

    // April 8th should contain the title.
    $this->assertCellHas(8, $node->label());

    // Test that the event does NOT appear in April 15th (after UNTIL date).
    $this->assertCellNotHas(15, $node->label());
  }

  /**
   * Test recurring events generated from multiple field deltas.
   */
  public function testRecurringEventsFromMultipleFieldItems(): void {
    $storage = FieldStorageConfig::loadByName('node', 'field_event_date');
    $storage->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    $storage->save();

    $node = $this->drupalCreateNode([
      'type' => 'event',
      'title' => 'Multi delta recurring',
      'field_event_date' => [
        [
          'value' => '2025-02-04T08:00:00',
          'end_value' => '2025-02-04T09:00:00',
          'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TU;UNTIL=20250410T065959Z;WKST=SU',
          'timezone' => 'UTC',
        ],
        [
          'value' => '2025-02-06T10:00:00',
          'end_value' => '2025-02-06T11:00:00',
          'rrule' => 'FREQ=WEEKLY;INTERVAL=1;BYDAY=TH;UNTIL=20250410T065959Z;WKST=SU',
          'timezone' => 'UTC',
        ],
      ],
    ]);

    $view = Views::getView('date_recur_calendar');
    $view->setDisplay('page_month');
    $view->execute();

    $february = new \DateTimeImmutable('2025-02-01');
    $this->drupalGet($view->getUrl([$february->format('Ym')]));

    $this->assertCellHas(4, $node->label());
    $this->assertCellHas(6, $node->label());
  }

}
