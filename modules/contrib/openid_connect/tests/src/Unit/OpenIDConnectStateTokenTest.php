<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Unit;

use Drupal\openid_connect\OpenIDConnectSessionInterface;
use Drupal\openid_connect\OpenIDConnectStateToken;
use Drupal\Tests\UnitTestCase;

/**
 * Test the OpenIDConnectStateToken class.
 *
 * @coversDefaultClass \Drupal\openid_connect\OpenIDConnectStateToken
 * @group openid_connect
 */
class OpenIDConnectStateTokenTest extends UnitTestCase {

  /**
   * Mock of the openid_connect.state_token service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectStateToken
   */
  protected $stateTokenService;

  /**
   * A mock of the openid_connect.session service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  protected $session;

  /**
   * The state token created for these tests.
   *
   * @var string
   */
  protected $stateToken;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the 'openid_connect.session' service.
    $this->session = $this->createMock(OpenIDConnectSessionInterface::class);

    $this->stateTokenService = new OpenIDConnectStateToken($this->session);

    // Set the state token and save the results.
    $this->stateToken = $this->stateTokenService->generateToken();
  }

  /**
   * Test the state tokens.
   *
   * @runInSeparateProcess
   */
  public function testConfirm(): void {
    $random = $this->randomMachineName();
    $this->session->expects($this->atLeast(2))
      ->method('retrieveStateToken')
      ->willReturnOnConsecutiveCalls($this->stateToken, $random, '');

    // Confirm the session matches the state token variable.
    $confirmResultTrue = $this->stateTokenService->confirm($this->stateToken);
    $this->assertEquals(TRUE, $confirmResultTrue);

    // Change the session variable.
    $this->session->saveStateToken($random);
    $confirmResultFalse = $this->stateTokenService->confirm($this->stateToken);

    // Assert the expected value no longer matches the session.
    $this->assertEquals(FALSE, $confirmResultFalse);

    // Check the state token.
    $confirmResultEmpty = $this->stateTokenService->confirm($this->stateToken);

    // Assert the session global does not contain the state token.
    $this->assertEquals(FALSE, $confirmResultEmpty);
  }

}
