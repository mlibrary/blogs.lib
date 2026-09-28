<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\openid_connect\Functional\OpenIdClientTestTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Tests that hook_openid_connect_userinfo_alter() never receives NULL.
 *
 * When a provider's userinfo endpoint is unreachable or returns non-JSON,
 * retrieveUserInfo() returns NULL. Before the fix, NULL was passed directly to
 * hook_openid_connect_userinfo_alter(), causing an immediate TypeError in any
 * implementation that declares the parameter as `array &$userinfo`.
 *
 * The test hook in openid_connect_test_hooks enforces the strict type; if NULL
 * is passed, PHP throws before the test assertions even run.
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnect
 * @group openid_connect
 */
class UserinfoAlterTest extends KernelTestBase {

  use OpenIdClientTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'file',
    'openid_connect',
    'openid_connect_test_hooks',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('externalauth', ['authmap']);
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user', 'openid_connect']);
  }

  /**
   * Test userInfoAlter hook.
   *
   * @see https://www.drupal.org/project/openid_connect/issues/3243493
   *
   * @covers ::completeAuthorization
   */
  public function testNullUserinfoIsNormalizedBeforeAlterHook(): void {
    // Activate the test hook so it captures whatever $userinfo it receives.
    // Because the hook declares `array &$userinfo`, PHP will throw a TypeError
    // if the caller passes NULL — that IS the regression signal.
    $this->container->get('state')->set('openid_connect_test_hooks.userinfo_alter', TRUE);

    // Inject a mock HTTP client that returns a non-JSON body. Json::decode()
    // returns NULL for non-JSON, so retrieveUserInfo() returns NULL.
    // Replace the container service BEFORE the plugin is lazily instantiated:
    // the plugin's create() pulls $container->get('http_client'), so this mock
    // will be picked up when getPlugin() is first called inside buildContext().
    $this->container->set('http_client', new Client([
      'handler' => HandlerStack::create(new MockHandler([
        new Response(200, ['Content-Type' => 'text/plain'], 'not-valid-json'),
      ])),
    ]));

    // Create a generic client entity with a userinfo endpoint.
    $clientEntity = $this->createTestClient('test_client', 'Test Client');

    // Tokens without an id_token so $user_data is NULL. Combined with the
    // NULL from retrieveUserInfo(), buildContext() will log "No user
    // information" and return FALSE before any account lookup.
    $tokens = ['access_token' => 'fake_access_token'];

    // This must not throw a TypeError. Without the fix, the strict `array`
    // type on the hook declaration would throw immediately.
    $result = $this->container->get('openid_connect.openid_connect')
      ->completeAuthorization($clientEntity, $tokens);

    $this->assertFalse($result, 'completeAuthorization() must return FALSE when no user info is available.');

    // Verify the hook received [] — never NULL.
    $captured = $this->container->get('state')
      ->get('openid_connect_test_hooks.userinfo_alter.data');
    $this->assertSame([], $captured, 'hook_openid_connect_userinfo_alter() must receive [] when retrieveUserInfo() returns NULL.');
  }

}
