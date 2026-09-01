<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that hook_openid_connect_redirect_logout_alter.
 *
 * @see https://www.drupal.org/project/openid_connect/issues/3577436
 * @group openid_connect
 */
class LogoutRedirectAlterHookTest extends BrowserTestBase {

  use OpenIdClientTestTrait;

  /**
   * The entity ID of the test client.
   *
   * Note: this is deliberately different from the plugin ID ('generic') so the
   * test can confirm the hook receives the entity ID, not the plugin ID.
   */
  const CLIENT_ID = 'my_test_provider';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
    'openid_connect_test_hooks',
    'externalauth',
    'user',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createTestClient(self::CLIENT_ID, 'My Test Provider');
    $this->placeBlock('system_menu_block:account');
    // Enable end_session so LogoutService builds the full context and invokes
    // the alter hook even when the provider has no end-session endpoint.
    $this->toggleEndSessionSetting(FALSE);
    $this->setRedirectLogoutUrl('/logout-destination');
  }

  /**
   * Verify hook receives the entity ID so the client entity can be loaded.
   *
   * Before the fix, $context['client'] held the plugin ID (e.g. 'generic').
   * After the fix it holds the entity ID (e.g. 'my_test_provider'), which
   * matches what OpenIDConnectClientEntity::load() expects.
   */
  public function testRedirectLogoutAlterContextContainsEntityId(): void {
    // Activate the hook implementation in the test module.
    \Drupal::state()->set('openid_connect_test_hooks.redirect_logout_alter', TRUE);

    // Create a user and link them to the OIDC client via the authmap table
    // so that LogoutService::getLoginProvider() resolves to our test client.
    $account = $this->createUser();
    /** @var \Drupal\externalauth\AuthmapInterface $authmap */
    $authmap = \Drupal::service('externalauth.authmap');
    $authmap->save(
      $account,
      sprintf('openid_connect.%s', self::CLIENT_ID),
      $this->randomMachineName()
    );

    $this->drupalLogin($account);
    $this->drupalGet('user');
    $this->getSession()->getPage()->clickLink('Log out');

    $data = \Drupal::state()->get('openid_connect_test_hooks.redirect_logout_alter.data');

    // The hook must have been invoked.
    $this->assertNotNull($data, 'The hook_openid_connect_redirect_logout_alter hook was invoked during logout.');

    // $context['client'] must equal the entity ID, not the plugin ID.
    $this->assertSame(
      self::CLIENT_ID,
      $data['client_id'],
      sprintf(
        'Expected $context["client"] to be the entity ID "%s", got "%s". '
          . 'If this is the plugin ID (e.g. "generic"), the regression from '
          . 'issue #3577436 has reappeared.',
        self::CLIENT_ID,
        $data['client_id'] ?? 'NULL'
      )
    );

    // The client entity must load successfully from the context value.
    $this->assertSame(
      self::CLIENT_ID,
      $data['loaded_entity_id'],
      'OpenIDConnectClientEntity::load($context["client"]) must return the '
        . 'correct entity. A NULL result means the context contains a plugin ID '
        . 'instead of the entity ID.'
    );
  }

}
