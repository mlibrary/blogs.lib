<?php

namespace Drupal\Tests\config_devel\Unit;

use PHPUnit\Framework\Attributes\Group;
use org\bovigo\vfs\vfsStream;
use Drupal\Component\Serialization\Yaml;

use Drupal\config_devel\EventSubscriber\ConfigDevelAutoExportSubscriber;

/**
 * @coversDefaultClass \Drupal\config_devel\EventSubscriber\ConfigDevelAutoExportSubscriber
 * @group config_devel
 */
#[Group('config_devel')]
class ConfigDevelAutoExportSubscriberTest extends ConfigDevelTestBase {

  /**
   * Test ConfigDevelAutoExportSubscriber::writeBackConfig().
   */
  public function testWriteBackConfig() {
    $config_data = array(
      'id' => $this->randomMachineName(),
      'langcode' => 'en',
      'uuid' => '836769f4-6791-402d-9046-cc06e20be87f',
    );

    $config = $this->createMock('\Drupal\Core\Config\Config');
    $config->expects($this->any())
      ->method('getName')
      ->willReturn($this->randomMachineName());
    $config->expects($this->any())
      ->method('get')
      ->willReturn($config_data);

    $file_names = array(
      vfsStream::url('public://' . $this->randomMachineName() . '.yml'),
      vfsStream::url('public://' . $this->randomMachineName() . '.yml'),
    );

    $configDevelSubscriber = new ConfigDevelAutoExportSubscriber($this->configFactory, $this->configManager, $this->eventDispatcher);
    $configDevelSubscriber->writeBackConfig($config, $file_names);

    $data = $config_data;
    unset($data['uuid']);
    unset($data['_core']);

    foreach ($file_names as $file_name) {
      $this->assertEquals($data, Yaml::decode(file_get_contents($file_name)));
    }
  }

}
