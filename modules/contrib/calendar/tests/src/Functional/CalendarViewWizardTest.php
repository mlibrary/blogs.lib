<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\user\UserInterface;

/**
 * Tests creating a calendar view via the Views UI wizard.
 *
 * @group calendar
 */
class CalendarViewWizardTest extends ViewTestBase {

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
    'node',
    'views_ui',
  ];

  /**
   * A user with admin privileges.
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = FALSE, $modules = []): void {
    parent::setUp($import_test_views, $modules);

    $this->adminUser = $this->drupalCreateUser([
      'administer views',
    ]);
    $this->drupalLogin($this->adminUser);

    $this->drupalCreateContentType(['type' => 'article']);
  }

  /**
   * Ensures a calendar view can be created from scratch with the calendar row.
   */
  public function testCreateCalendarViewFromScratch(): void {
    $this->drupalGet('admin/structure/views/add');

    $edit = [
      'label' => 'Calendar scratch view',
      'id' => 'calendar_scratch',
      'show[wizard_key]' => 'node',
      'page[create]' => TRUE,
      'page[title]' => 'Calendar scratch view',
      'page[path]' => 'calendar-scratch',
      'page[style][style_plugin]' => 'calendar',
    ];

    $this->submitForm($edit, 'Save and edit');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('The submitted value calendar_row in the Row element is not allowed.');

    // Apply the row plugin through the Views UI "Row" dialog.
    $row_plugin_url = 'admin/structure/views/nojs/display/calendar_scratch/page_1/row';
    $this->drupalGet($row_plugin_url);
    $this->assertSession()->elementExists('css', 'input[name="row[type]"][value="calendar_row"]');
    $this->submitForm(['row[type]' => 'calendar_row'], 'Apply');
    $this->assertSession()->pageTextNotContains('The submitted value calendar_row in the Row element is not allowed.');

    $this->drupalGet($row_plugin_url);
    $this->assertSession()->fieldValueEquals('row[type]', 'calendar_row');

    // Add a date contextual filter so calendar validation passes on save.
    $argument_add_url = 'admin/structure/views/nojs/add-handler/calendar_scratch/page_1/argument';
    $this->drupalGet($argument_add_url);
    $this->assertSession()->elementExists('css', 'input[name="name[node_field_data.created_year_month]"]');
    $this->submitForm(['name[node_field_data.created_year_month]' => TRUE], 'Add and configure contextual filters');
    $this->submitForm([], 'Apply');

    $this->drupalGet('admin/structure/views/view/calendar_scratch/edit/page_1');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextNotContains('A calendar date argument is required when using the calendar style.');
  }

}
