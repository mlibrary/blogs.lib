<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that the container compiles while externalauth is not installed.
 *
 * This is the state a site is in halfway through an upgrade from
 * openid_connect 1.x: the module is enabled, but externalauth has not been
 * installed yet because openid_connect_update_8203() has not run. Without
 * OpenidConnectServiceProvider the container fails to compile and the site
 * white screens with "You have requested a non-existent service".
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenidConnectServiceProvider
 * @group openid_connect
 * @see https://www.drupal.org/project/openid_connect/issues/3537149
 */
class ContainerWithoutExternalauthTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * Deliberately omits externalauth, which openid_connect.info.yml depends on.
   * KernelTestBase does not resolve module dependencies, so this reproduces
   * the pre-update module list.
   */
  protected static $modules = [
    'file',
    'openid_connect',
    'system',
    'user',
  ];

  /**
   * The container compiles and openid_connect services stay available.
   *
   * @covers ::alter
   */
  public function testContainerCompilesWithoutExternalauth(): void {
    $this->assertFalse(
      $this->container->has('externalauth.authmap'),
      'The test only makes sense while externalauth is absent.'
    );

    // Reaching this point already proves the container compiled. The services
    // that do not touch externalauth must also still be instantiable.
    $this->assertTrue($this->container->has('openid_connect.openid_connect'));
    $this->assertNotNull($this->container->get('openid_connect.session'));
    $this->assertNotNull($this->container->get('plugin.manager.openid_connect_client'));
  }

}
