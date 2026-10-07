<?php

namespace Drupal\Tests\linked_field\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\linked_field\LinkedFieldManagerInterface;

/**
 * Tests linked field manager.
 *
 * @group linked_field
 */
class LinkedFieldManagerTest extends KernelTestBase {

  /**
   * Linked field manager.
   *
   * @var \Drupal\linked_field\LinkedFieldManagerInterface
   */
  protected LinkedFieldManagerInterface $linkedFieldManager;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'linked_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->linkedFieldManager = $this->container->get('linked_field.manager');
  }

  /**
   * Tests buildDestinationUrl().
   *
   * @dataProvider providerTestBuildDestinationUrl
   */
  public function testBuildDestinationUrl(string $uri, string $expected) {
    $destination = $this->linkedFieldManager->buildDestinationUrl($uri);
    $this->assertStringContainsString($expected, $destination);
  }

  /**
   * Provider for testBuildDestinationUrl().
   */
  public static function providerTestBuildDestinationUrl(): array {
    return [
      [
        'uri' => 'node',
        'expected' => '/node',
      ],
      [
        'uri' => 'entity:node/1',
        'expected' => '/node/1',
      ],
    ];
  }

}
