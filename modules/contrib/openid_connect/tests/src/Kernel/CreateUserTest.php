<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\user\UserInterface;

/**
 * Tests the OpenIDConnect::createUser method.
 *
 * Validates that username and email fields pass entity-level validation and
 * that usernames are capped at Drupal's 60-character limit.
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnect
 * @group openid_connect
 */
class CreateUserTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'field',
    'file',
    'openid_connect',
    'system',
    'user',
  ];

  /**
   * The OpenIDConnect service under test.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected OpenIDConnect $openIdConnect;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('externalauth', ['authmap']);
    $this->installConfig(['user']);

    $this->openIdConnect = $this->container->get('openid_connect.openid_connect');
  }

  /**
   * Data provider for testCreateUserWithValidData.
   *
   * Each row: [sub, userinfo, expectedName, expectedEmail]
   * $expectedName is NULL when the username is auto-generated (fallback case),
   * which triggers a prefix assertion instead of an equality check.
   */
  public static function validUserDataProvider(): array {
    return [
      'preferred_username claim' => [
        'sub' => 'valid-sub-001',
        'userinfo' => ['email' => 'valid@example.com', 'preferred_username' => 'valid_user'],
        'expectedName' => 'valid_user',
        'expectedEmail' => 'valid@example.com',
      ],
      'username at max length' => [
        'sub' => 'max-length-sub-001',
        'userinfo' => ['email' => 'maxlength@example.com', 'preferred_username' => str_repeat('b', 60)],
        'expectedName' => str_repeat('b', 60),
        'expectedEmail' => 'maxlength@example.com',
      ],
      'fallback username when no claim present' => [
        'sub' => 'fallback-sub-001',
        'userinfo' => ['email' => 'fallback@example.com'],
        'expectedName' => NULL,
        'expectedEmail' => 'fallback@example.com',
      ],
    ];
  }

  /**
   * Tests that a user is created successfully with valid data.
   *
   * @covers ::createUser
   * @dataProvider validUserDataProvider
   */
  public function testCreateUserWithValidData(string $sub, array $userinfo, ?string $expectedName, string $expectedEmail): void {
    $user = $this->openIdConnect->createUser($sub, $userinfo, 'test_client');

    $this->assertInstanceOf(UserInterface::class, $user);
    $this->assertLessThanOrEqual(60, strlen($user->getAccountName()));
    $this->assertEquals($expectedEmail, $user->getEmail());

    if ($expectedName !== NULL) {
      $this->assertEquals($expectedName, $user->getAccountName());
    }
    else {
      $this->assertStringStartsWith('oidc_test_client_', $user->getAccountName());
    }
  }

  /**
   * Data provider for testCreateUserWithInvalidData.
   *
   * Each row: [preCreate, sub, userinfo]
   * $preCreate is an array with 'sub' and 'userinfo' keys for a user that must
   * exist before the main call, or NULL when no setup is required. This covers
   * both malformed-input failures and duplicate-value constraint failures in a
   * single test method.
   */
  public static function invalidUserDataProvider(): array {
    return [
      'username exceeds 60 characters' => [
        'preCreate' => NULL,
        'sub' => 'long-sub-001',
        'userinfo' => ['email' => 'toolong@example.com', 'preferred_username' => str_repeat('a', 61)],
      ],
      'invalid email address' => [
        'preCreate' => NULL,
        'sub' => 'invalid-email-sub-001',
        'userinfo' => ['email' => 'not-a-valid-email', 'preferred_username' => 'some_user'],
      ],
      'duplicate email address' => [
        'preCreate' => [
          'sub' => 'first-sub-001',
          'userinfo' => ['email' => 'duplicate@example.com', 'preferred_username' => 'first_user'],
        ],
        'sub' => 'second-sub-001',
        'userinfo' => ['email' => 'duplicate@example.com', 'preferred_username' => 'second_user'],
      ],
      'duplicate email address case-insensitive' => [
        'preCreate' => [
          'sub' => 'first-sub-002',
          'userinfo' => ['email' => 'duplicate2@example.com', 'preferred_username' => 'first_user_2'],
        ],
        'sub' => 'third-sub-001',
        'userinfo' => ['email' => 'DuPlicatE2@eXamPle.com', 'preferred_username' => 'third_user'],
      ],
    ];
  }

  /**
   * Tests that createUser throws when given invalid or duplicate data.
   *
   * When $preCreate is supplied the user is persisted first so that the main
   * call encounters a uniqueness violation rather than a format error.
   *
   * @covers ::createUser
   * @dataProvider invalidUserDataProvider
   */
  public function testCreateUserWithInvalidData(?array $preCreate, string $sub, array $userinfo): void {
    if ($preCreate !== NULL) {
      $this->openIdConnect->createUser($preCreate['sub'], $preCreate['userinfo'], 'test_client');
    }

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageMatches('/User validation failed/');

    $this->openIdConnect->createUser($sub, $userinfo, 'test_client');
  }

}
