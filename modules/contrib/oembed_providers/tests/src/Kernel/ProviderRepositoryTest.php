<?php

namespace Drupal\Tests\oembed_providers\Kernel;

use Drupal\Tests\media\Kernel\ProviderRepositoryTest as CoreProviderRepositoryTest;

/**
 * Run core ProviderRepositoryTest test against decorated Provider Repository.
 *
 * @covers \Drupal\media\OEmbed\ProviderRepository
 *
 * @group oembed_providers
 */
class ProviderRepositoryTest extends CoreProviderRepositoryTest {

  /**
   * Tests that hook_oembed_providers_alter() is invoked.
   */
  public function testProvidersAlterHook() {
    $this->container->get('module_installer')->install(['oembed_providers', 'oembed_providers_test']);
    $providers = $this->container->get('media.oembed.provider_repository')->getAll();
    $this->assertArrayHasKey('My Custom Provider', $providers);
  }

}
