<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for views field plugins.
 *
 * @see https://www.drupal.org/node/2455125
 *
 * @group Update
 */
class EntityArgumentUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/entity-id-argument.php',
    ];
  }

  /**
   * Tests that field plugins are updated properly.
   */
  public function testUpdateHookN() {
    $this->runUpdates();

    $config = \Drupal::config('views.view.test_entity_id_argument_update');
    $this->assertEquals('entity_target_id', $config->get('display.default.display_options.arguments.field_tags_target_id.plugin_id'));
    $this->assertEquals('taxonomy_term', $config->get('display.default.display_options.arguments.field_tags_target_id.target_entity_type_id'));
  }

}
