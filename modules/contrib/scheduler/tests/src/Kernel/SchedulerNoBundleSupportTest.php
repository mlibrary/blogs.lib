<?php

namespace Drupal\Tests\scheduler\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\scheduler\Form\SchedulerNoBundleSettingsForm;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests scheduler support for entity types without bundles.
 *
 * @group scheduler_kernel
 */
#[Group('scheduler_kernel')]
#[RunTestsInSeparateProcesses]
class SchedulerNoBundleSupportTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'filter',
    'datetime',
    'node',
    'options',
    'system',
    'text',
    'user',
    'views',
    'scheduler',
    'scheduler_no_bundle_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['scheduler']);
    $this->installEntitySchema('scheduler_test_no_bundle');
    $this->container->get('scheduler.manager')->invalidatePluginCache();
  }

  /**
   * Tests plugin discovery and settings resolution for no-bundle entities.
   */
  public function testNoBundleEntityTypeSettings(): void {
    /** @var \Drupal\scheduler\SchedulerManager $scheduler_manager */
    $scheduler_manager = $this->container->get('scheduler.manager');

    $plugin = $scheduler_manager->getPlugin('scheduler_test_no_bundle');
    $this->assertNotNull($plugin);
    $this->assertSame('', $plugin->typeFieldName());
    $this->assertFalse($scheduler_manager->hasBundleType('scheduler_test_no_bundle'));
    $this->assertSame([], $scheduler_manager->getEnabledTypes('scheduler_test_no_bundle', 'publish'));

    $this->container->get('config.factory')->getEditable('scheduler.no_bundle_entity_type_settings.scheduler_test_no_bundle')
      ->set('publish_enable', TRUE)
      ->set('unpublish_enable', FALSE)
      ->save();

    $enabled_types = $scheduler_manager->getEnabledTypes('scheduler_test_no_bundle', 'publish');
    $this->assertSame(['scheduler_test_no_bundle'], $enabled_types);

    $entity = $this->container->get('entity_type.manager')
      ->getStorage('scheduler_test_no_bundle')
      ->create([]);
    $this->assertTrue((bool) $scheduler_manager->getThirdPartySetting($entity, 'publish_enable', FALSE));
    $this->assertFalse((bool) $scheduler_manager->getThirdPartySetting($entity, 'unpublish_enable', TRUE));
  }

  /**
   * Tests that submitForm() skips alter-only form modes with no config entity.
   *
   * Form modes registered via hook_entity_form_mode_info_alter() without a
   * backing core.entity_form_mode.* config entity ("phantom" modes) must be
   * skipped during EntityFormDisplay saves. The scheduler_no_bundle_test module
   * registers such a mode to simulate the edge case.
   *
   * @see https://www.drupal.org/project/scheduler/issues/3355087
   */
  public function testSubmitFormSkipsPhantomFormMode(): void {
    // Confirm the phantom mode is present via the alter hook in the test module
    // but has no backing entity_form_mode config entity.
    $form_modes = $this->container->get('entity_display.repository')
      ->getFormModes('scheduler_test_no_bundle');
    $this->assertArrayHasKey('phantom', $form_modes, 'Phantom form mode registered by hook.');

    $phantom_config_entity = $this->container->get('entity_type.manager')
      ->getStorage('entity_form_mode')
      ->load('scheduler_test_no_bundle.phantom');
    $this->assertNull($phantom_config_entity, 'No backing entity_form_mode config entity for phantom mode.');

    // Calling submitForm() must not throw a TypeError when a phantom form mode
    // is present. Before the fix, EntityDisplayBase::calculateDependencies()
    // threw a fatal error on the null-loaded entity_form_mode.
    $form = SchedulerNoBundleSettingsForm::create($this->container);
    $form_state = new FormState();
    $form_state->set('entity_type_id', 'scheduler_test_no_bundle');
    $form_state->setValues([
      'scheduler_expand_fieldset' => 'when_required',
      'scheduler_fields_display_mode' => 'default',
      'scheduler_publish_enable' => TRUE,
      'scheduler_publish_past_date' => 'error',
      'scheduler_publish_past_date_created' => FALSE,
      'scheduler_publish_required' => FALSE,
      'scheduler_publish_revision' => FALSE,
      'scheduler_publish_touch' => FALSE,
      'scheduler_show_message_after_update' => TRUE,
      'scheduler_unpublish_enable' => FALSE,
      'scheduler_unpublish_required' => FALSE,
      'scheduler_unpublish_revision' => FALSE,
    ]);

    $form_array = [];
    $form->submitForm($form_array, $form_state);

    // Config must be saved (it is now written after the form display loop).
    $config = $this->config('scheduler.no_bundle_entity_type_settings.scheduler_test_no_bundle');
    $this->assertTrue((bool) $config->get('publish_enable'));
    $this->assertFalse((bool) $config->get('unpublish_enable'));
  }

}
