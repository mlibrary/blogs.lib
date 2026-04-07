<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\views\Entity\View;

/**
 * Tests that multiday hidden fields are respected in calendar items.
 *
 * @group calendar
 */
class CalendarMultidayHiddenFieldsTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'calendar',
    'calendar_module_test',
    'datetime',
    'datetime_range',
    'node',
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
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = ['calendar_module_test']): void {
    parent::setUp($import_test_views, $modules);

    // Force site timezone to UTC for deterministic date rendering.
    $this->config('system.date')->set('timezone', [
      'default' => 'UTC',
      'user' => ['configurable' => FALSE],
    ])->save();

    $this->drupalCreateContentType(['type' => 'article']);

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

    FieldStorageConfig::create([
      'field_name' => 'field_event_location',
      'entity_type' => 'node',
      'type' => 'string',
      'settings' => [
        'max_length' => 255,
      ],
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_event_note',
      'entity_type' => 'node',
      'type' => 'string',
      'settings' => [
        'max_length' => 255,
      ],
    ])->save();
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'field_event_location'),
      'bundle' => 'article',
      'label' => 'Event Location',
    ])->save();
    FieldConfig::create([
      'field_storage' => FieldStorageConfig::loadByName('node', 'field_event_note'),
      'bundle' => 'article',
      'label' => 'Event Note',
    ])->save();

    $this->configureMultidayViewFields();
  }

  /**
   * Ensures hidden fields are excluded from multiday rows.
   */
  public function testMultidayHiddenFields(): void {
    $start = new \DateTimeImmutable('2025-04-03 09:00:00', new \DateTimeZone('UTC'));
    $end = new \DateTimeImmutable('2025-04-05 17:00:00', new \DateTimeZone('UTC'));
    $single_day = new \DateTimeImmutable('2025-04-10 09:00:00', new \DateTimeZone('UTC'));

    $multiday_node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Hidden Fields Multi-day Event',
      'field_event_location' => 'Conference Room A',
      'field_event_note' => 'Do not show',
      'field_event_time' => [
        'value' => $start->format('Y-m-d\TH:i:s'),
        'end_value' => $end->format('Y-m-d\TH:i:s'),
      ],
    ]);

    $single_day_node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Visible Fields Single-day Event',
      'field_event_location' => 'Conference Room B',
      'field_event_note' => 'Do not show',
      'field_event_time' => [
        'value' => $single_day->format('Y-m-d\TH:i:s'),
        'end_value' => $single_day->format('Y-m-d\TH:i:s'),
      ],
    ]);

    $path = 'test-multiday/month/' . $start->format('Ym');
    $this->drupalGet($path);

    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains($multiday_node->label());
    $session->pageTextContains($single_day_node->label());

    // Title and location should render.
    $session->elementExists('css', '.calendar .contents .views-field-title');
    $session->elementExists('css', '.calendar .contents .calendar-location');

    // Created field should be hidden for multiday rows.
    $session->elementNotExists(
      'xpath',
      "//div[contains(@class,'contents')][.//*[contains(.,'Hidden Fields Multi-day Event')]]//*[contains(@class,'calendar-created')]"
    );

    // Created field should still show for single-day rows.
    $session->elementExists(
      'xpath',
      "//div[contains(@class,'contents')][.//*[contains(.,'Visible Fields Single-day Event')]]//*[contains(@class,'calendar-created')]"
    );

    // Excluded field should never render.
    $session->elementNotExists('css', '.calendar .contents .calendar-note');
  }

  /**
   * Adds extra fields and hides one of them for multiday rows.
   */
  protected function configureMultidayViewFields(): void {
    $view = View::load('multiday_month_calendar');
    if (!$view) {
      $this->fail('Expected multiday_month_calendar view to exist.');
    }

    $display = $view->getDisplay('default');
    $fields = $display['display_options']['fields'] ?? [];

    $fields['created'] = [
      'id' => 'created',
      'table' => 'node_field_data',
      'field' => 'created',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'entity_type' => 'node',
      'entity_field' => 'created',
      'plugin_id' => 'field',
      'label' => '',
      'exclude' => FALSE,
      'alter' => [
        'alter_text' => FALSE,
        'make_link' => FALSE,
        'absolute' => FALSE,
        'word_boundary' => FALSE,
        'ellipsis' => FALSE,
        'strip_tags' => FALSE,
        'trim' => FALSE,
        'html' => FALSE,
      ],
      'element_type' => 'span',
      'element_class' => 'calendar-created',
      'element_label_type' => '',
      'element_label_class' => '',
      'element_label_colon' => TRUE,
      'element_wrapper_type' => '',
      'element_wrapper_class' => '',
      'element_default_classes' => TRUE,
      'empty' => '',
      'hide_empty' => FALSE,
      'empty_zero' => FALSE,
      'hide_alter_empty' => TRUE,
      'click_sort_column' => 'value',
      'type' => 'string',
      'settings' => [
        'link_to_entity' => TRUE,
      ],
      'group_column' => 'value',
      'group_columns' => [],
      'group_rows' => TRUE,
      'delta_limit' => 0,
      'delta_offset' => 0,
      'delta_reversed' => FALSE,
      'delta_first_last' => FALSE,
      'multi_type' => 'separator',
      'separator' => ', ',
      'field_api_classes' => FALSE,
    ];

    if (!isset($fields['title'])) {
      $this->fail('Expected title field to exist in multiday_month_calendar view.');
    }

    $fields['field_event_location'] = [
      'id' => 'field_event_location',
      'table' => 'node__field_event_location',
      'field' => 'field_event_location',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'entity_type' => 'node',
      'entity_field' => 'field_event_location',
      'plugin_id' => 'field',
      'label' => '',
      'exclude' => FALSE,
      'alter' => [
        'alter_text' => FALSE,
        'make_link' => FALSE,
        'absolute' => FALSE,
        'word_boundary' => FALSE,
        'ellipsis' => FALSE,
        'strip_tags' => FALSE,
        'trim' => FALSE,
        'html' => FALSE,
      ],
      'element_type' => 'span',
      'element_class' => 'calendar-location',
      'element_label_type' => '',
      'element_label_class' => '',
      'element_label_colon' => FALSE,
      'element_wrapper_type' => '',
      'element_wrapper_class' => '',
      'element_default_classes' => TRUE,
      'empty' => '',
      'hide_empty' => FALSE,
      'empty_zero' => FALSE,
      'hide_alter_empty' => TRUE,
      'click_sort_column' => 'value',
      'type' => 'string',
      'settings' => [],
      'group_column' => 'value',
      'group_columns' => [],
      'group_rows' => TRUE,
      'delta_limit' => 0,
      'delta_offset' => 0,
      'delta_reversed' => FALSE,
      'delta_first_last' => FALSE,
      'multi_type' => 'separator',
      'separator' => ', ',
      'field_api_classes' => FALSE,
    ];

    $fields['field_event_note'] = [
      'id' => 'field_event_note',
      'table' => 'node__field_event_note',
      'field' => 'field_event_note',
      'relationship' => 'none',
      'group_type' => 'group',
      'admin_label' => '',
      'entity_type' => 'node',
      'entity_field' => 'field_event_note',
      'plugin_id' => 'field',
      'label' => '',
      'exclude' => TRUE,
      'alter' => [
        'alter_text' => FALSE,
        'make_link' => FALSE,
        'absolute' => FALSE,
        'word_boundary' => FALSE,
        'ellipsis' => FALSE,
        'strip_tags' => FALSE,
        'trim' => FALSE,
        'html' => FALSE,
      ],
      'element_type' => 'span',
      'element_class' => 'calendar-note',
      'element_label_type' => '',
      'element_label_class' => '',
      'element_label_colon' => FALSE,
      'element_wrapper_type' => '',
      'element_wrapper_class' => '',
      'element_default_classes' => TRUE,
      'empty' => '',
      'hide_empty' => FALSE,
      'empty_zero' => FALSE,
      'hide_alter_empty' => TRUE,
      'click_sort_column' => 'value',
      'type' => 'string',
      'settings' => [],
      'group_column' => 'value',
      'group_columns' => [],
      'group_rows' => TRUE,
      'delta_limit' => 0,
      'delta_offset' => 0,
      'delta_reversed' => FALSE,
      'delta_first_last' => FALSE,
      'multi_type' => 'separator',
      'separator' => ', ',
      'field_api_classes' => FALSE,
    ];

    $display['display_options']['fields'] = $fields;
    $display['display_options']['style']['options']['multiday_hidden'] = [
      'created' => 'created',
    ];

    $displays = $view->get('display');
    $displays['default'] = $display;
    $view->set('display', $displays);
    $view->save();
  }

}
