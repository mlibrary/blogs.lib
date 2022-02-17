<?php

namespace Drupal\Tests\og_menu\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\og\OgGroupAudienceHelper;
use Drupal\Tests\ConfigTestTrait;

/**
 * @group og_menu
 */
class OgMenuConfigImportTest extends KernelTestBase {

  use ConfigTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'og',
    'og_menu',
    'system',
    'user',
  ];

  /**
   * Checks the creation of the group reference field from module config.
   */
  public function testModuleInstallationWithDefaultConfig() {
    /** @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    $module_installer = $this->container->get('module_installer');

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
    $entity_field_manager = $this->container->get('entity_field.manager');

    $module_installer->install(['og_menu_test']);
    $this->assertArrayHasKey(OgGroupAudienceHelper::DEFAULT_FIELD, $entity_field_manager->getFieldStorageDefinitions('ogmenu_instance'));
  }

  /**
   * Checks the creation of the group reference field from imported config.
   */
  public function testConfigImport() {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    // Make sure the system.site configuration is available, so that the site
    // UUID exists.
    $this->installConfig(['system']);

    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    $src_dir = __DIR__ . '/../../modules/og_menu_test/config/install';
    $target_dir = Settings::get('config_sync_directory');

    $names = [
      'field.field.ogmenu_instance.test.og_audience',
      'field.storage.ogmenu_instance.og_audience',
      'node.type.group',
      'og_menu.ogmenu.test',
    ];

    foreach ($names as $name) {
      self::assertTrue($file_system->copy("$src_dir/$name.yml", "$target_dir/$name.yml"));
    }

    // Import the content of the sync directory.
    $this->configImporter()->import();
    $this->assertArrayHasKey(OgGroupAudienceHelper::DEFAULT_FIELD, \Drupal::service('entity_field.manager')->getFieldStorageDefinitions('ogmenu_instance'));
  }

}
