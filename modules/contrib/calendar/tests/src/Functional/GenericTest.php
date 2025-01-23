<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Drupal\Tests\views\Functional\ViewTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;
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
   * @var array
   */
  public static array $testViews = ['content_authored_on_calendar'];

  /**
   * A user with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * Sets up the test environment.
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
  }

  /**
   * Tests calendar admin settings page.
   */
  public function testCalendarAdminPage() {
    $this->drupalGet('admin/config/date/calendar');
    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->buttonExists('Save configuration')->submit();
    $session->pageTextContains('The configuration options have been saved');
  }

  /**
   * Tests if the 'Add view from template' link exists.
   */
  public function testAddViewFromTemplateLinkExists() {
    $this->drupalGet('admin/structure/views');
    $this->assertSession()->statusCodeEquals(200);

    // Retrieve the link and verify it points to the expected URL.
    $session = $this->assertSession();
    $session->linkExists('Add view from template');
  }

  /**
   * Test an event appears in the right cell in the calendar.
   */
  public function testEventAppearsOnCorrectDayInCalendar() {
    $view = Views::getView('content_authored_on_calendar');
    $view->setDisplay('page_month');
    $view->execute();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    // Set the date for the node to the first of the current month.
    // First day of the current month.
    $date = date('Y-m-01');

    // Create a node with the specified date.
    $node = [
      'type' => 'article',
      'title' => 'Test Node for Calendar',
      'body' => [['value' => $this->randomMachineName(32)]],
      'uid' => $this->adminUser->id(),
      'created' => strtotime($date),
      'nid' => 1,
      'status' => 1,
    ];
    $node = Node::create($node);
    $node->save();

    // Navigate to the calendar page.
    $this->drupalGet($view->getUrl());

    // Locate the calendar cell for the specified date.
    $day_of_month = date('j', strtotime($date));
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

}
