<?php

namespace Drupal\Tests\oembed_providers\Unit;

use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\media\OEmbed\ProviderRepository;
use Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator;
use Drupal\Tests\media\Unit\ProviderRepositoryTest as CoreProviderRepositoryTest;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

/**
 * Run core ProviderRepositoryTest tests against decorated ProviderRepository.
 *
 * Because ProviderRepository is subject to change, there is the possibility
 * that the decorator could become out of date and break core functionality. By
 * running the extended core unit tests against the decorated class, the intent
 * is to catch such instances.
 *
 * @covers \Drupal\oembed_providers\OEmbed\ProviderRepositoryDecorator
 *
 * @group oembed_providers
 */
class ProviderRepositoryTest extends CoreProviderRepositoryTest {

  /**
   * The HTTP client handler which will serve responses.
   *
   * @var \GuzzleHttp\Handler\MockHandler
   */
  protected $responses;

  /**
   * The key-value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  protected $keyValue;

  /**
   * The time that the current test began.
   *
   * @var int
   */
  protected $currentTime;

  /**
   * The mocked logger channel.
   *
   * @var \Psr\Log\LoggerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * {@inheritdoc}
   *
   * Copied from parent class and modified as noted below.
   */
  protected function setUp(): void {
    parent::setUp();

    $config_factory = $this->getConfigFactoryStub([
      'media.settings' => [
        'oembed_providers_url' => 'https://oembed.com/providers.json',
      ],
      // Begin setUp override.
      'oembed_providers.settings' => [
        'external_fetch' => TRUE,
      ],
      // End setUp override.
    ]);

    $key_value_factory = new KeyValueMemoryFactory();
    $this->keyValue = $key_value_factory->get('media');

    $this->currentTime = time();
    $time = $this->prophesize('\Drupal\Component\Datetime\TimeInterface');
    $time->getCurrentTime()->willReturn($this->currentTime);

    $this->logger = $this->prophesize('\Psr\Log\LoggerInterface');
    $logger_factory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $logger_factory->get('media')->willReturn($this->logger);

    $this->responses = new MockHandler();
    $client = new Client([
      'handler' => HandlerStack::create($this->responses),
    ]);

    // Begin setUp override.
    $decorated = new ProviderRepository(
      $client,
      $config_factory,
      $time->reveal(),
      $key_value_factory,
      $logger_factory->reveal()
    );

    $config_storage = $this->prophesize('\Drupal\Core\Config\Entity\ConfigEntityStorageInterface');
    $config_storage->loadMultiple()->willReturn([]);

    $entity_type_manager = $this->prophesize('\Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_type_manager->getStorage('oembed_provider')->willReturn($config_storage);

    $module_hander = $this->prophesize('\Drupal\Core\Extension\ModuleHandlerInterface');

    $repository = new ProviderRepositoryDecorator(
      $decorated,
      $entity_type_manager->reveal(),
      $client,
      $config_factory,
      $time->reveal(),
      $key_value_factory,
      $logger_factory->reveal(),
      $module_hander->reveal()
    );

    // Use ReflectionProperty to override all properties on parent class.
    $reflector = new \ReflectionProperty(parent::class, 'repository');
    $reflector->setAccessible(TRUE);
    $reflector->setValue($this, $repository);

    $reflector = new \ReflectionProperty(parent::class, 'responses');
    $reflector->setAccessible(TRUE);
    $reflector->setValue($this, $this->responses);

    $reflector = new \ReflectionProperty(parent::class, 'keyValue');
    $reflector->setAccessible(TRUE);
    $reflector->setValue($this, $this->keyValue);

    $reflector = new \ReflectionProperty(parent::class, 'currentTime');
    $reflector->setAccessible(TRUE);
    $reflector->setValue($this, $this->currentTime);

    $reflector = new \ReflectionProperty(parent::class, 'logger');
    $reflector->setAccessible(TRUE);
    $reflector->setValue($this, $this->logger);
    // End setUp override.
  }

}
