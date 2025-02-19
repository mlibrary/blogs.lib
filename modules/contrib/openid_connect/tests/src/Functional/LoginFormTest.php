<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the login form openid_connect alterations.
 *
 * @group openid_connect
 */
class LoginFormTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Confirm a user cannot access another user's connected accounts page.
   *
   * @param string $position
   *   The position setting to test.
   * @param int $expectedButtonCount
   *   The expected number of buttons displayed on the login page.
   *
   * @dataProvider dataProviderForTestLoginForm
   */
  public function testLoginForm(
    string $position = 'below',
    int $expectedButtonCount = 2,
  ): void {
    $client = $this->createTestClient('test', 'Test OIDC Client');

    $this->updateFormPosition($position);
    $this->assertEquals($position, \Drupal::configFactory()->get('openid_connect.settings')->get('user_login_display'));

    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals(200);
    // Build xpath query getting two form elements by css class.
    $formSubmitButtons = $this->xpath('//input[contains(@class, "form-submit")]');
    // Ensure we have the expected amount of form submit buttons.
    $this->assertCount($expectedButtonCount, $formSubmitButtons);

    // Confirm the button labels are as expected.
    match($position) {
      'above', 'replace' => $this->assertEquals(sprintf('Log in with %s', $client->label()), $formSubmitButtons[0]->getValue()),
      'below' => $this->assertEquals(sprintf('Log in with %s', $client->label()), $formSubmitButtons[1]->getValue()),
      'hidden' => $this->assertEquals('Log in', $formSubmitButtons[0]->getValue())
    };
  }

  /**
   * Test the OpenID Connect login form `replace` option.
   */
  public function testReplaceLoginForm(): void {
    $client = $this->createTestClient('test', 'Test OIDC Client');
    $this->updateFormPosition('replace');
    $this->assertEquals('replace', \Drupal::configFactory()->get('openid_connect.settings')->get('user_login_display'));

    $this->drupalGet('user/login');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->buttonExists(sprintf('Log in with %s', $client->label()));
    $this->assertSession()->elementNotExists('css', 'form#user-login-form');

    $this->drupalGet('user/login', ['query' => ['showcore' => '1']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->elementExists('css', 'form#user-login-form');
    $this->assertSession()->elementNotExists('css', 'form#openid-connect-login-form');
  }

  /**
   * Data provider for the testLoginForm method.
   *
   * @return array[]
   *   Parameters to pass to testDisconnectPermissionDenied.
   */
  public static function dataProviderForTestLoginForm(): array {
    return [
      'Test the OpenID form is above the normal Drupal login form' => ['above', 2],
      'Test the OpenID form is below the normal Drupal login form' => ['below', 2],
      'Test the OpenID form is hidden' => ['hidden', 1],
      'Test the Drupal login form is replaced with the OpenID form' => ['replace', 1],
    ];
  }

  /**
   * Helper function to update the form position configuration.
   *
   * @param string $position
   *   The position to set the form.
   */
  private function updateFormPosition(string $position): void {
    // Test code goes here.
    \Drupal::configFactory()->getEditable('openid_connect.settings')->set('user_login_display', $position)->save();
  }

}
