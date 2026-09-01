<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\openid_connect\EventSubscriber\OpenIDConnectAutoLogin;
use Drupal\openid_connect\OpenIDConnectClaims;
use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Drupal\openid_connect\Plugin\OpenIDConnectClientManager;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;

/**
 * Tests how the auto-login subscriber resolves the current route.
 *
 * The subscriber runs on every request while autostart is enabled, so the route
 * name it reads decides whether an anonymous visitor is redirected to the
 * provider or served the page they asked for. The interesting case cannot be
 * reached over HTTP: a request that never went through the router has no route
 * object at all, and the subscriber has to treat that as "not a login page"
 * rather than redirecting blindly.
 *
 * @coversDefaultClass \Drupal\openid_connect\EventSubscriber\OpenIDConnectAutoLogin
 * @group openid_connect
 */
class OpenIDConnectAutoLoginTest extends UnitTestCase {

  const CLIENT_CONFIG_NAME = 'openid_connect.client.test_client';

  /**
   * The response the mocked client returns from authorize().
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected Response $authorizeResponse;

  /**
   * The mocked OpenID Connect client.
   *
   * @var \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->authorizeResponse = new Response('', 302, ['Location' => 'https://example.com/authorize']);

    $this->client = $this->createMock(OpenIDConnectClientInterface::class);
    $this->client->method('getEndpoints')
      ->willReturn(['authorization' => 'https://example.com/authorize']);
    $this->client->method('getPluginId')->willReturn('generic');
    $this->client->method('getParentEntityId')->willReturn('test_client');
  }

  /**
   * Build the subscriber with a single enabled client and autostart on.
   *
   * @return \Drupal\openid_connect\EventSubscriber\OpenIDConnectAutoLogin
   *   The subscriber under test.
   */
  protected function createSubscriber(): OpenIDConnectAutoLogin {
    $settings = $this->createMock(ImmutableConfig::class);
    $settings->method('get')->willReturnMap([
      ['autostart_login', TRUE],
    ]);

    $clientConfig = $this->createMock(ImmutableConfig::class);
    $clientConfig->method('get')->willReturnMap([
      ['status', TRUE],
      ['plugin', 'generic'],
      ['settings', []],
      ['id', 'test_client'],
    ]);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory->method('listAll')
      ->willReturn([self::CLIENT_CONFIG_NAME, 'system.site']);
    $configFactory->method('get')
      ->willReturnCallback(function (string $name) use ($settings, $clientConfig) {
        return $name === 'openid_connect.settings' ? $settings : $clientConfig;
      });

    $pluginManager = $this->createMock(OpenIDConnectClientManager::class);
    $pluginManager->method('createInstance')->willReturn($this->client);

    $logger = $this->createMock(LoggerChannelInterface::class);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($logger);

    $currentUser = $this->createMock(AccountInterface::class);
    $currentUser->method('isAnonymous')->willReturn(TRUE);

    $claims = $this->createMock(OpenIDConnectClaims::class);
    $claims->method('getScopes')->willReturn('openid email');

    return new OpenIDConnectAutoLogin(
      $currentUser,
      $pluginManager,
      $configFactory,
      $loggerFactory,
      $claims,
      $this->createMock(OpenIDConnectSessionInterface::class)
    );
  }

  /**
   * Build a request event, optionally carrying a matched route.
   *
   * @param string $path
   *   The request path.
   * @param string|null $routeName
   *   The matched route name, or NULL to leave the request unrouted.
   *
   * @return \Symfony\Component\HttpKernel\Event\RequestEvent
   *   The request event to hand to the subscriber.
   */
  protected function createRequestEvent(string $path, ?string $routeName): RequestEvent {
    $request = Request::create($path);
    $request->setSession(new Session(new MockArraySessionStorage()));

    if ($routeName !== NULL) {
      $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $routeName);
      $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route($request->getPathInfo()));
    }

    return new RequestEvent(
      $this->createMock(HttpKernelInterface::class),
      $request,
      HttpKernelInterface::MAIN_REQUEST
    );
  }

  /**
   * A matched login route starts the provider redirect.
   *
   * This is the control for the tests below: it proves the fixture really is
   * configured to auto-login, so a missing response elsewhere means the route
   * was rejected rather than that autostart was never enabled.
   *
   * @covers ::login
   * @dataProvider dataProviderForLoginRoutes
   */
  public function testLoginRoutesRedirect(string $routeName, string $path): void {
    $this->client->expects($this->once())
      ->method('authorize')
      ->willReturn($this->authorizeResponse);

    $event = $this->createRequestEvent($path, $routeName);
    $this->createSubscriber()->login($event);

    $this->assertSame($this->authorizeResponse, $event->getResponse());
  }

  /**
   * Data provider for testLoginRoutesRedirect().
   *
   * @return array<string, array{string, string}>
   *   Route names and paths that must trigger auto-login.
   */
  public static function dataProviderForLoginRoutes(): array {
    return [
      'user.login' => ['user.login', '/user/login'],
      'user.register' => ['user.register', '/user/register'],
      'user.pass' => ['user.pass', '/user/password'],
    ];
  }

  /**
   * An unrouted request is left alone.
   *
   * A request with no route object cannot be identified as a login page, so the
   * subscriber must fall through instead of redirecting. This is the case that
   * a functional test cannot reach: the router either matches a route or
   * throws before this subscriber's priority is reached.
   *
   * @covers ::login
   */
  public function testUnroutedRequestIsIgnored(): void {
    $this->client->expects($this->never())->method('authorize');

    $event = $this->createRequestEvent('/user/login', NULL);
    $this->createSubscriber()->login($event);

    $this->assertNull($event->getResponse());
  }

  /**
   * A route outside the login routes is left alone.
   *
   * @covers ::login
   */
  public function testUnrelatedRouteIsIgnored(): void {
    $this->client->expects($this->never())->method('authorize');

    $event = $this->createRequestEvent('/some/other/page', 'example.some_other_page');
    $this->createSubscriber()->login($event);

    $this->assertNull($event->getResponse());
  }

  /**
   * The showcore query parameter bypasses auto-login on a login route.
   *
   * @covers ::login
   */
  public function testShowcoreBypassesAutoLogin(): void {
    $this->client->expects($this->never())->method('authorize');

    $event = $this->createRequestEvent('/user/login?showcore=1', 'user.login');
    $this->createSubscriber()->login($event);

    $this->assertNull($event->getResponse());
  }

}
