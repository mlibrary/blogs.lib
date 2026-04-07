<?php

declare(strict_types=1);

namespace Drupal\Tests\calendar\Unit;

use Drupal\calendar\Plugin\views\row\Calendar;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the Calendar row plugin.
 *
 * @group calendar
 * @coversDefaultClass \Drupal\calendar\Plugin\views\row\Calendar
 */
class CalendarRowPluginTest extends UnitTestCase {

  /**
   * The calendar row plugin.
   */
  protected ?Calendar $calendarRow = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the Calendar row.
    $plugin = new Calendar(
      [],
      'calendar_row',
      ['provider' => 'calendar'],
      $this->createMock(DateFormatterInterface::class),
      $this->createMock(EntityFieldManagerInterface::class),
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(ModuleHandlerInterface::class),
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(EntityTypeBundleInfoInterface::class)
    );

    $this->calendarRow = $plugin;
  }

  /**
   * Test that isEntityAlreadyRendered() correctly tracks duplicate entity IDs.
   */
  public function testIsEntityAlreadyRendered(): void {
    // Use reflection to access the protected method.
    $reflection = new \ReflectionClass($this->calendarRow);
    $method = $reflection->getMethod('isEntityAlreadyRendered');

    $this->assertFalse($method->invoke($this->calendarRow, 123), 'First call with new ID has not been rendered yet.');
    $this->assertTrue($method->invoke($this->calendarRow, 123), 'Second call with same ID has already been rendered.');
    $this->assertFalse($method->invoke($this->calendarRow, 456), 'Different ID has not been rendered yet.');
    $this->assertTrue($method->invoke($this->calendarRow, 456), 'Same different ID has already been rendered.');
    $this->assertTrue($method->invoke($this->calendarRow, 123), 'Original ID is still considered rendered.');
  }

}
