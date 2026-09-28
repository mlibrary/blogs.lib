<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\Core\StreamWrapper\PrivateStream;
use GuzzleHttp\ClientInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\user\UserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Tests getUserProfilePhoto() via the public saveUserinfo() entry point.
 *
 * Validates that:
 *   - Unsafe URL protocols (javascript:, ftp:, data:) are rejected and no
 *     photo is saved.
 *   - Responses with a non-image Content-Type header are rejected.
 *   - Responses whose body fails Drupal image validation are rejected.
 *   - A valid HTTPS URL returning a real image produces a saved file entity
 *     attached to the user's image field.
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnect
 * @group openid_connect
 */
class GetUserProfilePhotoTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'field',
    'file',
    'image',
    'openid_connect',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Schema tables that live outside entity storage.
    $this->installSchema('externalauth', ['authmap']);
    $this->installSchema('user', ['users_data']);

    // Install module configuration (user.settings, roles, etc.).
    $this->installConfig(['user', 'openid_connect']);

    // Add an image field to the user entity BEFORE installing the entity
    // schema, so the field columns are part of the generated table.
    FieldStorageConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'type' => 'image',
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => 'user_picture',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Profile picture',
    ])->save();

    // Install entity schemas now that field definitions are in place.
    $this->installEntitySchema('user');
    $this->installEntitySchema('file');

    // Ensure the public files directory exists so file entities can be saved.
    $publicPath = 'public://';
    $this->container->get('file_system')->prepareDirectory(
      $publicPath,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
    );

    // Map the 'picture' userinfo claim to the user_picture field.
    $this->config('openid_connect.settings')
      ->set('userinfo_mappings', ['user_picture' => 'picture'])
      ->save();
  }

  /**
   * Returns an OpenIDConnect service with the given HTTP client injected.
   *
   * All other dependencies are pulled from the test container, so the service
   * behaves exactly as in production, except for HTTP I/O.
   */
  private function createServiceWithHttpClient(ClientInterface $httpClient): OpenIDConnect {
    return new OpenIDConnect(
      $this->container->get('config.factory'),
      $this->container->get('externalauth.authmap'),
      $this->container->get('externalauth.externalauth'),
      $this->container->get('entity_type.manager'),
      $this->container->get('entity_field.manager'),
      $this->container->get('current_user'),
      $this->container->get('user.data'),
      $this->container->get('email.validator'),
      $this->container->get('messenger'),
      $this->container->get('module_handler'),
      $this->container->get('logger.factory'),
      $this->container->get('file_system'),
      $this->container->get('openid_connect.session'),
      $this->container->get('file.repository'),
      $httpClient,
      $this->container->get('image.factory'),
    );
  }

  /**
   * Builds a Guzzle mock client from a list of queued responses.
   *
   * @param \GuzzleHttp\Psr7\Response[] $responses
   *   Ordered list of responses to return for each outgoing request.
   */
  private function createMockHttpClient(array $responses): Client {
    return new Client([
      'handler' => HandlerStack::create(new MockHandler($responses)),
    ]);
  }

  /**
   * Creates and persists a minimal user account.
   */
  private function createTestAccount(): UserInterface {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create([
        'name' => 'photo_test',
        'mail' => 'photo_test@example.com',
        'status' => 1,
      ]);
    $account->save();
    return $account;
  }

  /**
   * Calls saveUserinfo() with the 'picture' claim set to the given URL.
   *
   * This is the public entry point that internally calls getUserProfilePhoto().
   */
  private function callSaveUserinfoWithPictureUrl(
    OpenIDConnect $service,
    UserInterface $account,
    string $pictureUrl,
  ): void {
    $context = [
      'tokens' => [],
      'user_data' => [],
      'userinfo' => ['picture' => $pictureUrl],
      'plugin_id' => 'test_client',
      'sub' => 'test_subject_123',
    ];
    $service->saveUserinfo($account, $context);
  }

  /**
   * Returns raw bytes for a valid 1×1 PNG image generated via PHP GD.
   */
  private function getValidPngBytes(): string {
    return base64_decode(
      'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='
    );
  }

  /**
   * Data provider of URLs whose protocols must be rejected.
   *
   * @return array<string, array{string}>
   *   Array of test parameters to pass to testU unsafe protocols.
   */
  public static function providerUnsafeUrls(): array {
    return [
      'javascript protocol' => ['javascript:alert("xss")'],
      'ftp protocol' => ['ftp://example.com/photo.jpg'],
      'file protocol (local filesystem)' => ['file:///etc/passwd'],
      'data protocol' => ['data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAAC0lEQVQI12NgAAIABQ=='],
    ];
  }

  /**
   * Unsafe URL protocols must be rejected before any HTTP request is made.
   *
   * Only http and https are permitted. Any other scheme
   * must be stripped and fail the absolute-URL check,
   * leaving the user's picture field unset.
   *
   * The mock HTTP client has an empty queue: if the code accidentally reaches
   * the network layer, MockHandler throws, surfacing
   * the regression immediately.
   *
   * @covers ::getUserProfilePhoto
   * @dataProvider providerUnsafeUrls
   */
  public function testUnsafeProtocolIsRejected(string $url): void {
    $service = $this->createServiceWithHttpClient($this->createMockHttpClient([]));
    $account = $this->createTestAccount();

    $this->callSaveUserinfoWithPictureUrl($service, $account, $url);

    $this->assertNull(
      $account->user_picture->entity,
      sprintf('"%s" must be rejected and must not result in a saved profile photo.', $url),
    );
  }

  /**
   * A response with a non-image Content-Type must be rejected.
   *
   * @covers ::getUserProfilePhoto
   */
  public function testNonImageContentTypeIsRejected(): void {
    $mockClient = $this->createMockHttpClient([
      new Response(200, ['Content-Type' => 'text/html; charset=utf-8'], '<html>not an image</html>'),
    ]);
    $service = $this->createServiceWithHttpClient($mockClient);
    $account = $this->createTestAccount();

    $this->callSaveUserinfoWithPictureUrl($service, $account, 'https://example.com/page');

    $this->assertNull(
      $account->user_picture->entity,
      'Responses with a non-image Content-Type must not produce a saved profile photo.',
    );
  }

  /**
   * A response that claims image/* but contains corrupt data must be rejected.
   *
   * The Content-Type header alone is not sufficient — the downloaded bytes must
   * pass Drupal's image-factory validation before the file is saved.
   *
   * @covers ::getUserProfilePhoto
   */
  public function testCorruptImageBodyIsRejected(): void {
    $mockClient = $this->createMockHttpClient([
      new Response(200, ['Content-Type' => 'image/png'], 'this is definitely not png data'),
    ]);
    $service = $this->createServiceWithHttpClient($mockClient);
    $account = $this->createTestAccount();

    $this->callSaveUserinfoWithPictureUrl($service, $account, 'https://example.com/fake.png');

    $this->assertNull(
      $account->user_picture->entity,
      'Corrupt image body must be rejected even when the Content-Type claims image/*.',
    );
  }

  /**
   * A valid HTTPS URL that returns a genuine image saves a file entity.
   *
   * @covers ::getUserProfilePhoto
   */
  public function testValidImageIsSaved(): void {
    $mockClient = $this->createMockHttpClient([
      new Response(200, ['Content-Type' => 'image/png'], $this->getValidPngBytes()),
    ]);
    $service = $this->createServiceWithHttpClient($mockClient);
    $account = $this->createTestAccount();

    $this->callSaveUserinfoWithPictureUrl($service, $account, 'https://example.com/avatar.png');

    $file = $account->user_picture->entity;
    $this->assertInstanceOf(
      FileInterface::class,
      $file,
      'A valid image response must produce a saved file entity on the user picture field.',
    );
    $this->assertMatchesRegularExpression(
      '/^public:\/\/user-picture--[a-f0-9\-]+\.png$/',
      $file->getFileUri(),
      'Saved file URI must follow the user-picture--{uuid}.png naming pattern.',
    );
  }

  /**
   * Data provider for URI scheme cases.
   *
   * @return array<string, array{string}>
   *   Different URI schemes for testing image saving behavior.
   */
  public static function providerUriSchemes(): array {
    return [
      'public scheme' => ['public'],
      'private scheme' => ['private'],
    ];
  }

  /**
   * The image must be saved to the URI scheme configured on the field.
   *
   * When an image field defines a specific uri_scheme (e.g. "private"),
   * the saved file must use that scheme rather than always defaulting to
   * "public". This is a regression test for the bug where the picture claim
   * always wrote to public:// regardless of the field's uri_scheme setting.
   *
   * @covers ::getUserProfilePhoto
   * @dataProvider providerUriSchemes
   */
  public function testImageSavedToFieldUriScheme(string $uriScheme): void {
    if ($uriScheme === 'private') {
      $this->setSetting('file_private_path', $this->siteDirectory . '/private');
      // Rebuild the container so Drupal's StreamWrapperManager includes
      // private:// in its scheme registry (needed by FileRepository).
      $this->container = $this->container->get('kernel')->rebuildContainer();
      // Register private:// with PHP's native stream wrapper layer. The
      // container rebuild makes Drupal aware of the wrapper, but
      // stream_wrapper_register() is only called during the initial kernel
      // boot (when file_private_path was not yet set), so we must register
      // it manually here.
      if (!in_array('private', stream_get_wrappers())) {
        stream_wrapper_register('private', PrivateStream::class);
      }
    }

    // Update the field storage to use the given URI scheme.
    $fieldStorage = FieldStorageConfig::loadByName('user', 'user_picture');
    $fieldStorage->setSetting('uri_scheme', $uriScheme);
    $fieldStorage->save();

    // prepareDirectory() takes its first argument by reference.
    $directory = sprintf('%s://', $uriScheme);
    $this->container->get('file_system')->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
    );

    $mockClient = $this->createMockHttpClient([
      new Response(200, ['Content-Type' => 'image/png'], $this->getValidPngBytes()),
    ]);
    $service = $this->createServiceWithHttpClient($mockClient);
    $account = $this->createTestAccount();

    $this->callSaveUserinfoWithPictureUrl($service, $account, 'https://example.com/avatar.png');

    $file = $account->user_picture->entity;
    $this->assertInstanceOf(
      FileInterface::class,
      $file,
      "A valid image must be saved when using the {$uriScheme}:// scheme.",
    );
    $this->assertStringStartsWith(
      $uriScheme . '://',
      $file->getFileUri(),
      "The saved file must use the field's configured uri_scheme ({$uriScheme}://).",
    );
  }

}
