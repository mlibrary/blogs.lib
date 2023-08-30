<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the hook_update_n functions.
 *
 * @group scheduler_content_moderation_integration
 */
class HookUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/update/db_dumps.php',
      __DIR__ . '/../../fixtures/update/db_dumps_binary.php',
      __DIR__ . '/../../fixtures/update/content_moderation_config.php',
    ];
    $core_files = [
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.3.0.filled.standard.php.gz',
      DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
    foreach ($core_files as $file) {
      if (file_exists($file)) {
        // Pick the first core dump file that is found (to cater for running at
        // various Drupal versions) and place it at the start of the array
        // because this file has to be executed first.
        array_unshift($this->databaseDumpFiles, $file);
        break;
      }
    }
    if (count($this->databaseDumpFiles) == 2) {
      // The test could be skipped using $this->markTestSkipped() if no core
      // dump file could be found. However, it is better for now to fail, to
      // alert that this test needs to be updated.
    }
  }

  /**
   * Tests the hook update functions.
   */
  public function testUpdates() {
    // Without the call to entityUpdate() we get error "Content: The Publish on
    // field needs to be installed".
    \Drupal::service('scheduler.manager')->entityUpdate();

    $display_repository = \Drupal::service('entity_display.repository');
    $form_display = $display_repository->getFormDisplay('node', 'article');

    // Show that both state components are hidden by default, even if the
    // entity bundle is enabled for scheduling.
    $publish_state_component = $form_display->getComponent('publish_state');
    $this->assertNull($publish_state_component, 'The publish_state field is hidden before the update.');

    $unpublish_state_component = $form_display->getComponent('unpublish_state');
    $this->assertNull($unpublish_state_component, 'The unpublish_state field is hidden before the update.');

    // Run all hook updates required.
    $this->runUpdates();

    // Get the updated form display.
    $form_display = $display_repository->getFormDisplay('node', 'article');

    // Test that the publish_state form field is now displayed, but the
    // unpublish state field remains hidden.
    $publish_state_component = $form_display->getComponent('publish_state');
    $this->assertNotNull($publish_state_component, 'The publish_state field is enabled after the update.');

    $unpublish_state_component = $form_display->getComponent('unpublish_state');
    $this->assertNull($unpublish_state_component, 'The unpublish_state field is hidden after the update.');

  }

}
