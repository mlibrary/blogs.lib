<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Unit\Entity;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\externalauth\AuthmapInterface;
use Drupal\openid_connect\Entity\OpenIDConnectClientEntity;
use Drupal\openid_connect\Plugin\OpenIDConnectClient\OpenIDConnectGenericClient;
use Drupal\openid_connect\Plugin\OpenIDConnectClientCollection;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Drupal\Tests\UnitTestCase;

/**
 * Add unit tests for the the OpenIDConnectClientEntity class.
 *
 * @coversDefaultClass \Drupal\openid_connect\Entity\OpenIDConnectClientEntity
 * @group openid_connect
 */
class OpenIDConnectClientEntityTest extends UnitTestCase {
  const CLIENT_ID = 'test_client_id';
  const CLIENT_SECRET = 'test_client_secret';
  const PLUGIN_ID = 'generic';
  const PLUGIN_VALUES = [
    'id' => 'generic',
    'label' => 'Test Plugin',
    'plugin' => self::PLUGIN_ID,
    'settings' => [
      'client_id' => self::CLIENT_ID,
      'client_secret' => self::CLIENT_SECRET,
      'issuer_url' => '',
      'authorization_endpoint' => 'https://example.com/oauth2/authorize',
      'token_endpoint' => 'https://example.com/oauth2/token',
      'userinfo_endpoint' => 'https://example.com/oauth2/userinfo',
      'end_session_endpoint' => '',
      'scopes' => ['openid', 'email'],
    ],
  ];

  const ENTITY_TYPE = 'openid_connect_client';

  /**
   * Mock the plugin.manager.openid_connect_client service.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $pluginManager;

  /**
   * Mock the externalauth.authmap service.
   *
   * @var \Drupal\externalauth\AuthmapInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $authmap;

  /**
   * Mock the config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * Mock the entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity class being tested.
   *
   * @var \Drupal\openid_connect\Entity\OpenIDConnectClientEntity
   */
  protected $entity;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->pluginManager = $this->createMock(OpenIDConnectClientManager::class);
    $this->authmap = $this->createMock(AuthmapInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('plugin.manager.openid_connect_client', $this->pluginManager);
    $container->set('externalauth.authmap', $this->authmap);
    $container->set('config.factory', $this->configFactory);
    $container->set('entity_type.manager', $this->entityTypeManager);
    \Drupal::setContainer($container);

    $this->entity = new OpenIDConnectClientEntity(self::PLUGIN_VALUES, self::ENTITY_TYPE);
  }

  /**
   * Test the getPluginId() method.
   */
  public function testGetPluginId(): void {
    $this->assertEquals(self::PLUGIN_ID, $this->entity->getPluginId());
  }

  /**
   * Test the delete() method.
   */
  public function testDelete(): void {
    $entity_id = self::PLUGIN_VALUES['id'];
    $this->authmap->expects($this->once())
      ->method('deleteProvider')
      ->with("open_connect.{$entity_id}");

    $entityStorage = $this->createMock(EntityStorageInterface::class);
    $entityStorage->expects($this->once())
      ->method('delete')
      ->with([$entity_id => $this->entity]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with(self::ENTITY_TYPE)
      ->willReturn($entityStorage);

    $this->entity->delete();
  }

  /**
   * Test the getPlugin() method.
   */
  public function testGetPlugin(): void {
    $entity_id = self::PLUGIN_VALUES['id'];

    $this->configFactory->expects($this->never())
      ->method('get');

    $rawSettings = self::PLUGIN_VALUES['settings'];
    $pluginMock = $this->createMock(OpenIDConnectGenericClient::class);
    $pluginMock->expects($this->once())
      ->method('getConfiguration')
      ->willReturn($rawSettings);
    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->with($entity_id, $rawSettings)
      ->willReturn($pluginMock);

    $plugin = $this->entity->getPlugin();

    $config = $plugin->getConfiguration();

    $this->assertEquals(self::CLIENT_ID, $config['client_id']);
    $this->assertEquals(self::CLIENT_SECRET, $config['client_secret']);
  }

  /**
   * Test the getPluginCollections() method.
   */
  public function testGetPluginCollections(): void {
    $entity_id = self::PLUGIN_VALUES['id'];

    $this->configFactory->expects($this->never())
      ->method('get');

    $rawSettings = self::PLUGIN_VALUES['settings'];
    $pluginMock = $this->createMock(OpenIDConnectGenericClient::class);
    $this->pluginManager->expects($this->once())
      ->method('createInstance')
      ->with($entity_id, $rawSettings)
      ->willReturn($pluginMock);

    $collections = $this->entity->getPluginCollections();

    $this->assertArrayHasKey('settings', $collections);
    $this->assertInstanceOf(OpenIDConnectClientCollection::class, $collections['settings']);
  }

}
