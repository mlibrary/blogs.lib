<?php

namespace Drupal\Tests\file_entity\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\pathauto\Entity\PathautoPattern;
use Drupal\Tests\Traits\Core\PathAliasTestTrait;

/**
 * Tests Pathauto support.
 *
 * @dependencies pathauto
 *
 * @group file_entity
 */
#[Group('file_entity')]
#[RunTestsInSeparateProcesses]
class FileEntityPathautoTest extends FileEntityTestBase {

  use PathAliasTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['pathauto'];

  /**
   * Tests Pathauto support.
   */
  public function testPathauto() {
    $pattern = PathautoPattern::create([
      'id' => mb_strtolower($this->randomMachineName()),
      'type' => 'canonical_entities:file',
      'pattern' => '/files/[file:name]',
      'weight' => 0,
    ]);
    $pattern->save();

    $this->createFileEntity(['filename' => 'example.png']);

    $this->assertPathAliasExists('/files/examplepng', NULL, NULL, 'file alias exists');
  }

}
