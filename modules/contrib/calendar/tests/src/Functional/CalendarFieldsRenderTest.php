<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Functional;

use Drupal\Tests\views\Functional\ViewTestBase;

/**
 * Tests Views fields theming within calendar cells.
 *
 * @group calendar
 */
class CalendarFieldsRenderTest extends ViewTestBase {

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
    'node',
  ];

  /**
   * Views used by this test.
   *
   * @var string[]
   */
  public static array $testViews = [
    'content_authored_on_calendar',
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
  }

  /**
   * Ensures views_view_fields markup is rendered inside calendar items.
   */
  public function testCalendarCellsUseViewsFieldsTheme(): void {
    $created = new \DateTimeImmutable('2025-01-15 09:00:00', new \DateTimeZone('UTC'));
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => 'Calendar Fields Row Test',
      'created' => $created->getTimestamp(),
    ]);

    $path = 'calendar-created/month/' . $created->format('Ym');
    $this->drupalGet($path);

    $session = $this->assertSession();
    $session->statusCodeEquals(200);
    $session->pageTextContains($node->label());

    // Verify the standard Views field wrappers are present in the cell.
    $session->elementExists('css', '.calendar .contents .views-field-title');
    $session->elementExists('css', '.calendar .contents .views-field-created');
  }

}
