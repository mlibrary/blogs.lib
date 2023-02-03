<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Kernel;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\system\Entity\Action;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\scheduler\Traits\SchedulerSetupTrait;

/**
 * Base class for the Scheduler Content Moderation tests.
 */
abstract class SchedulerContentModerationTestBase extends KernelTestBase {

  use ContentModerationTestTrait;
  use SchedulerSetupTrait;

  use NodeCreationTrait {
    // These two functions are defined in BrowserTestBase but not KernelTestBase
    // so get them here, as they are very useful.
    getNodeByTitle as drupalGetNodeByTitle;
    createNode as drupalCreateNode;
  }

  /**
   * Moderation info service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * The moderation workflow.
   *
   * @var \Drupal\workflows\Entity\Workflow
   */
  protected $workflow;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'action',
    'content_moderation',
    'datetime',
    'field',
    // Filter is needed for CreateNode filter_default_format().
    'filter',
    'language',
    'node',
    'options',
    'scheduler',
    'scheduler_content_moderation_integration',
    'system',
    'user',
    'workflows',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('content_moderation_state');
    $this->installConfig('content_moderation');
    $this->installConfig('filter');

    // Scheduler calls some config entity, instead of installing whole modules
    // default config just create the ones we need..
    DateFormat::create([
      'id' => 'long',
      'label' => 'Custom long date',
      'pattern' => 'l, F j, Y - H:i',
    ])->save();
    Action::create([
      'id' => 'node_publish_action',
      'label' => 'Custom node_publish_action',
      'type' => 'node',
      'plugin' => 'entity:publish_action:node',
    ])->save();
    Action::create([
      'id' => 'node_unpublish_action',
      'label' => 'Custom node_unpublish_action',
      'type' => 'node',
      'plugin' => 'entity:unpublish_action:node',
    ])->save();

    $this->configureExampleNodeType();
    $this->configureEditorialWorkflow();

    $this->moderationInfo = \Drupal::service('content_moderation.moderation_information');
  }

  /**
   * Configure example node type.
   */
  protected function configureExampleNodeType() {
    $node_type = NodeType::create([
      'type' => 'example',
      'label' => 'Example',
    ]);
    $node_type->setThirdPartySetting('scheduler', 'publish_enable', TRUE);
    $node_type->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE);
    $node_type->save();
  }

  /**
   * Configures the editorial workflow for the example node type.
   */
  protected function configureEditorialWorkflow() {
    $this->workflow = $this->createEditorialWorkflow();
    $this->workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'example');
    $this->workflow->save();
  }

  /**
   * Test data for supported entity types.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public function dataEntityTypes() {
    $data = [
      '#node' => ['node', 'example'],
    ];
    return $data;
  }

}
