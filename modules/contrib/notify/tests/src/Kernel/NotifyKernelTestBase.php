<?php

namespace Drupal\Tests\notify\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\notify\Traits\NotifyCommonTrait;

/**
 * Base class for Notify kernel tests.
 */
abstract class NotifyKernelTestBase extends EntityKernelTestBase {

  use AssertMailTrait;
  use NotifyCommonTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'user',
    'system',
    'field',
    'text',
    'filter',
    'entity_test',
    'node',
    'notify',
  ];

  /**
   * The node type to test with.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $nodeType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install database schemes.
    $this->installSchema('node', 'node_access');
    $this->installSchema('notify', 'notify');
    $this->installSchema('notify', 'notify_subscriptions');
    $this->installSchema('notify', 'notify_unpublished_queue');

    // Install configuration.
    $this->installConfig(['node', 'notify', 'user']);

    // Set site name and mail address.
    $this->config('system.site')
      ->set('name', 'Drupal')
      ->set('mail', 'sitetest@example.com')
      ->save();

    // Create a content type.
    $this->nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->nodeType->save();

    // Subscribe new users to articles by default.
    $this->setNotifySettings([
      'notify_nodetypes' => [
        'article' => 1,
      ],
    ]);

    // Create the anonymous user.
    $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'uid' => 0,
        'status' => 0,
        'name' => '',
      ])
      ->save();
  }

}
