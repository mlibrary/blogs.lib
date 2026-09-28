<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Unit;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\openid_connect\ParamConverter\OpenIDConnectEntityConverter;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Route;

/**
 * Tests OpenIDConnectEntityConverter.
 *
 * @coversDefaultClass \Drupal\openid_connect\ParamConverter\OpenIDConnectEntityConverter
 * @group openid_connect
 */
class OpenIDConnectEntityConverterTest extends UnitTestCase {

  /**
   * The entity storage mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $storage;

  /**
   * The converter under test.
   *
   * @var \Drupal\openid_connect\ParamConverter\OpenIDConnectEntityConverter
   */
  protected OpenIDConnectEntityConverter $converter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // @phpstan-ignore drupal.entityStoragePropertyAssignment
    $this->storage = $this->createMock(EntityStorageInterface::class);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('openid_connect_client')
      ->willReturn($this->storage);

    $entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $this->converter = new OpenIDConnectEntityConverter($entityTypeManager, $entityRepository);
  }

  /**
   * @covers ::convert
   */
  public function testConvertReturnsClientFoundByEntityId(): void {
    $client = $this->createMock(OpenIDConnectClientEntityInterface::class);

    $this->storage->method('load')->with('my_client')->willReturn($client);

    $result = $this->converter->convert('my_client', [], 'openid_connect_client', []);

    $this->assertSame($client, $result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertFallsBackToSlugWhenIdNotFound(): void {
    $client = $this->createMock(OpenIDConnectClientEntityInterface::class);

    $this->storage->method('load')->with('my-slug')->willReturn(NULL);
    $this->storage->method('loadByProperties')
      ->with(['settings.provider_slug' => 'my-slug'])
      ->willReturn([$client]);

    $result = $this->converter->convert('my-slug', [], 'openid_connect_client', []);

    $this->assertSame($client, $result);
  }

  /**
   * @covers ::convert
   */
  public function testConvertReturnsNullWhenNeitherIdNorSlugMatches(): void {
    $this->storage->method('load')->with('unknown')->willReturn(NULL);
    $this->storage->method('loadByProperties')
      ->with(['settings.provider_slug' => 'unknown'])
      ->willReturn([]);

    $result = $this->converter->convert('unknown', [], 'openid_connect_client', []);

    $this->assertNull($result);
  }

  /**
   * Tests that a slug shared by two clients refuses to resolve.
   *
   * If the unique constraint is somehow violated and two clients share a slug,
   * the converter must refuse to resolve.
   *
   * @covers ::convert
   */
  public function testConvertReturnsNullForDuplicateSlugs(): void {
    $client1 = $this->createMock(OpenIDConnectClientEntityInterface::class);
    $client2 = $this->createMock(OpenIDConnectClientEntityInterface::class);

    $this->storage->method('load')->with('shared-slug')->willReturn(NULL);
    $this->storage->method('loadByProperties')
      ->with(['settings.provider_slug' => 'shared-slug'])
      ->willReturn([$client1, $client2]);

    $result = $this->converter->convert('shared-slug', [], 'openid_connect_client', []);

    $this->assertNull($result);
  }

  /**
   * Data provider for testApplies().
   *
   * @return array<string, array{array<string,string>, bool}>
   *   The test data.
   */
  public static function appliesProvider(): array {
    return [
      'correct type returns true' => [['type' => 'openid_connect_entity'], TRUE],
      'other entity type returns false' => [['type' => 'entity:node'], FALSE],
      'no type key returns false' => [[], FALSE],
    ];
  }

  /**
   * @covers ::applies
   * @dataProvider appliesProvider
   *
   * @param array<string,string> $definition
   *   The definition to test.
   * @param bool $expected
   *   The expected result.
   */
  public function testApplies(array $definition, bool $expected): void {
    $route = $this->createMock(Route::class);
    $this->assertSame($expected, $this->converter->applies($definition, 'openid_connect_client', $route));
  }

}
