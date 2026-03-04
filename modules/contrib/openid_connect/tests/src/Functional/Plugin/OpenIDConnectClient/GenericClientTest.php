<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional\Plugin\OpenIDConnectClient;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the Generic OpenID Connect client.
 *
 * @group openid_connect
 * @group openid_connect_client
 */
class GenericClientTest extends BrowserTestBase {

  const ID = 'plugin_id';

  const LABEL = 'Test Generic Client';

  const PLUGIN_TITLE = 'Generic OAuth 2.0';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['openid_connect'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected $profile = 'minimal';

  /**
   * The admin account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminAccount;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminAccount = $this->createUser([
      'administer openid connect clients',
    ]);
  }

  /**
   * Tests the generic client.
   */
  public function testGenericClient() {
    $this->drupalLogin($this->adminAccount);
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists(self::PLUGIN_TITLE);
    $this->clickLink(self::PLUGIN_TITLE);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/admin/config/people/openid-connect/add/generic');

    $edit = [
      'label' => self::LABEL,
      'id' => self::ID,
      'settings[client_id]' => $this->randomString(6),
      'settings[client_secret]' => $this->randomString(6),
      'settings[iss_allowed_domains]' => '',
      'settings[use_well_known]' => 0,
      'settings[issuer_url]' => '',
      'settings[authorization_endpoint]' => 'https://example.com/oauth2/authorize',
      'settings[token_endpoint]' => 'https://example.com/oauth2/token',
      'settings[userinfo_endpoint]' => 'https://example.com/oauth2/userinfo',
      'settings[end_session_endpoint]' => '',
      'settings[scopes]' => 'openid email',
    ];

    $this->submitForm($edit, 'Create OpenID Connect client');
    $this->assertSession()->pageTextContains(sprintf('OpenID Connect client %s has been added.', self::LABEL));
    $this->assertSession()->addressEquals('/admin/config/people/openid-connect');
    $this->assertSession()->linkByHrefExists(sprintf('/admin/config/people/openid-connect/%s/edit', self::ID));
    $this->clickLink('Edit');

    // Assert the edit entity form is displayed.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(sprintf('/admin/config/people/openid-connect/%s/edit', self::ID));

    // Assert the form fields are what were submitted.
    $this->assertSession()->fieldValueEquals('label', self::LABEL);
    $this->assertSession()->fieldValueEquals('id', self::ID);
    $this->assertSession()->fieldValueEquals('settings[client_id]', $edit['settings[client_id]']);
    $this->assertSession()->fieldValueEquals('settings[client_secret]', $edit['settings[client_secret]']);
    $this->assertSession()->fieldValueEquals('settings[iss_allowed_domains]', $edit['settings[iss_allowed_domains]']);
    $this->assertSession()->checkboxNotChecked('settings[use_well_known]');
    $this->assertSession()->fieldValueEquals('settings[issuer_url]', $edit['settings[issuer_url]']);
    $this->assertSession()->fieldValueEquals('settings[authorization_endpoint]', $edit['settings[authorization_endpoint]']);
    $this->assertSession()->fieldValueEquals('settings[token_endpoint]', $edit['settings[token_endpoint]']);
    $this->assertSession()->fieldValueEquals('settings[userinfo_endpoint]', $edit['settings[userinfo_endpoint]']);
    $this->assertSession()->fieldValueEquals('settings[end_session_endpoint]', $edit['settings[end_session_endpoint]']);
    $this->assertSession()->fieldValueEquals('settings[scopes]', $edit['settings[scopes]']);

    // Test specific for #3266452.
    // @see https://www.drupal.org/project/openid_connect/issues/3266452
    $newEdit = $edit;
    $newEdit['settings[scopes]'] = '';
    $this->submitForm($newEdit, 'Save');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains(sprintf('OpenID Connect client %s has been updated.', self::LABEL));
    $this->assertSession()->addressEquals('/admin/config/people/openid-connect');
    $this->assertSession()->linkByHrefExists(sprintf('/admin/config/people/openid-connect/%s/edit', self::ID));
    $this->clickLink('Edit');
    // Assert the edit entity form is displayed.
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals(sprintf('/admin/config/people/openid-connect/%s/edit', self::ID));
    // Assert the scopes are still empty.
    $this->assertSession()->fieldValueEquals('settings[scopes]', '');
  }

}
