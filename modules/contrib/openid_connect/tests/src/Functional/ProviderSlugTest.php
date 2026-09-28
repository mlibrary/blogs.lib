<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

/**
 * Tests the provider_slug functionality.
 *
 * @group openid_connect
 */
class ProviderSlugTest extends OpenIdConnectTestBase {

  use OpenIdClientTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'openid_connect',
    'externalauth',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the provider_slug is saved correctly when creating a client.
   */
  public function testProviderSlugSave(): void {
    // Create an admin user with permission to manage OpenID Connect clients.
    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Go to the add client page.
    $this->drupalGet('/admin/config/people/openid-connect/add/generic');
    $this->assertSession()->statusCodeEquals(200);

    // Fill in the form with a custom provider_slug.
    $edit = [
      'label' => 'Test Provider',
      'id' => 'test_provider',
      'settings[client_id]' => 'test_client_id',
      'settings[client_secret]' => 'test_client_secret',
      'settings[provider_slug]' => 'custom_slug',
    ];
    $this->submitForm($edit, 'Create OpenID Connect client');

    // Verify the client was created.
    $this->assertSession()->pageTextContains('OpenID Connect client Test Provider has been added.');

    // Load the client entity and verify the provider_slug was saved.
    $client = $this->getTestClient('test_provider');
    $this->assertEquals('custom_slug', $client->getPlugin()->getProviderSlug());

    // Verify the redirect URL contains the custom slug.
    $this->drupalGet('/admin/config/people/openid-connect/test_provider/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('custom_slug');

    // Logout and confirm the slug resolves (403 = route found, access denied)
    // and the entity ID also resolves since it is always available.
    $this->drupalLogout();
    $this->assertRedirectRoute('custom_slug', 403);
    $this->assertRedirectRoute('test_provider', 403);
    $this->assertInitiateRoute('custom_slug', 403);
    $this->assertInitiateRoute('test_provider', 403);

    // Login and edit the custom slug.
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/people/openid-connect/test_provider/edit');

    // Edit the client and change the provider_slug.
    $edit = [
      'settings[provider_slug]' => 'updated_slug',
    ];
    $this->submitForm($edit, 'Save');

    // Verify the client was updated.
    $this->assertSession()->pageTextContains('OpenID Connect client Test Provider has been updated.');

    // Load the client entity and verify the provider_slug was updated.
    $client = $this->getTestClient('test_provider');
    $this->assertEquals('updated_slug', $client->getPlugin()->getProviderSlug());

    // Verify the redirect URL contains the updated slug.
    $this->drupalGet('/admin/config/people/openid-connect/test_provider/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('updated_slug');

    // Logout and confirm the updated slug resolves.
    $this->drupalLogout();
    $this->assertRedirectRoute('updated_slug', 403);
    $this->assertRedirectRoute('test_provider', 403);
    $this->assertInitiateRoute('updated_slug', 403);
    $this->assertInitiateRoute('test_provider', 403);

    // Login and edit the custom slug.
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/people/openid-connect/test_provider/edit');
    // Test with an empty provider_slug (should fall back to entity ID).
    $edit = [
      'settings[provider_slug]' => '',
    ];
    $this->submitForm($edit, 'Save');

    // Verify the client was updated.
    $this->assertSession()->pageTextContains('OpenID Connect client Test Provider has been updated.');

    // Load the client entity and verify the provider_slug is empty.
    $client = $this->getTestClient('test_provider');
    $this->assertEmpty($client->getPlugin()->getProviderSlug());

    // Verify the redirect URL contains the entity ID as fallback.
    $this->drupalGet('/admin/config/people/openid-connect/test_provider/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('test_provider');

    // Old slugs no longer resolve; entity ID still works.
    $this->drupalLogout();
    $this->assertRedirectRoute('updated_slug', 404);
    $this->assertRedirectRoute('custom_slug', 404);
    $this->assertRedirectRoute('', 404);
    $this->assertRedirectRoute('test_provider', 403);
    $this->assertInitiateRoute('updated_slug', 404);
    $this->assertInitiateRoute('custom_slug', 404);
    $this->assertInitiateRoute('test_provider', 403);
    $this->assertInitiateRoute('', 404);

    // Validate the provider_slug is unique.
    $this->drupalLogin($account);
    // Go to the add client page.
    $this->drupalGet('/admin/config/people/openid-connect/add/generic');
    $this->assertSession()->statusCodeEquals(200);

    // Fill in the form with a custom provider_slug.
    $edit = [
      'label' => 'Unique One',
      'id' => 'unique_one',
      'settings[client_id]' => 'test_client_id',
      'settings[client_secret]' => 'test_client_secret',
      'settings[provider_slug]' => 'unique-one-custom',
    ];
    $this->submitForm($edit, 'Create OpenID Connect client');

    // Verify the client was created.
    $this->assertSession()->pageTextContains('OpenID Connect client Unique One has been added.');

    // Load the client entity and verify the provider_slug was saved.
    $client = $this->getTestClient('unique_one');
    $this->assertEquals('unique-one-custom', $client->getPlugin()->getProviderSlug());

    $this->drupalGet('/admin/config/people/openid-connect/add/generic');
    $this->assertSession()->statusCodeEquals(200);

    // Fill in the form with a custom provider_slug.
    $edit = [
      'label' => 'Unique Two',
      'id' => 'unique_two',
      'settings[client_id]' => 'test_client_id',
      'settings[client_secret]' => 'test_client_secret',
      'settings[provider_slug]' => 'unique-one-custom',
    ];
    $this->submitForm($edit, 'Create OpenID Connect client');

    // Verify the client was created.
    $this->assertSession()->pageTextContains('The provider slug must be unique.');
    $edit['settings[provider_slug]'] = '';
    $this->submitForm($edit, 'Create OpenID Connect client');
    $this->assertSession()->pageTextContains('OpenID Connect client Unique Two has been added.');

    // Load the client entity and verify the provider_slug was saved.
    $client = $this->getTestClient('unique_two');
    $this->assertEquals('', $client->getPlugin()->getProviderSlug());

    $this->drupalGet('/admin/config/people/openid-connect/unique_two/edit');
    // Test editing and confirm a unique code fails).
    $edit = [
      'settings[provider_slug]' => 'unique-one-custom',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('The provider slug must be unique.');

  }

  /**
   * Tests that a slug which can not be used in a URL is rejected.
   *
   * The redirect URL puts the slug in a single path segment, so a slug that is
   * not a valid path segment makes route generation throw. That would take
   * down the login flow and the very form needed to correct the slug, which is
   * the failure this whole feature exists to prevent.
   *
   * @see https://www.drupal.org/project/openid_connect/issues/3359789
   */
  public function testProviderSlugFormatValidation(): void {
    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    $storage = \Drupal::entityTypeManager()->getStorage('openid_connect_client');

    // A random machine name is a valid slug on its own, so each case below is
    // rejected only because of the character joining the two halves.
    $name = $this->randomMachineName();
    $invalid_slugs = [
      // A path separator is what actually throws during URL generation.
      $name . '/' . $name,
      $name . ' ' . $name,
      $name . '%2f' . $name,
      $name . '?' . $name,
      $name . '#' . $name,
      $name . ':' . $name,
    ];

    foreach ($invalid_slugs as $invalid_slug) {
      $this->drupalGet('/admin/config/people/openid-connect/add/generic');
      $this->submitForm([
        'label' => 'Invalid Slug',
        'id' => 'invalid_slug',
        'settings[client_id]' => 'test_client_id',
        'settings[client_secret]' => 'test_client_secret',
        'settings[provider_slug]' => $invalid_slug,
      ], 'Create OpenID Connect client');

      // The form comes back with an error instead of a saved client or a
      // white screen.
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->pageTextContains('The provider slug can only contain letters, numbers, dots, hyphens and underscores.');
      $this->assertSession()->pageTextNotContains('OpenID Connect client Invalid Slug has been added.');
      $storage->resetCache(['invalid_slug']);
      $this->assertNull($storage->load('invalid_slug'), sprintf('No client was saved for the slug "%s".', $invalid_slug));
    }

    // Surrounding whitespace is not an error, it is trimmed away, so the slug
    // that ends up in the redirect URL is the one the administrator meant.
    $this->drupalGet('/admin/config/people/openid-connect/add/generic');
    $this->submitForm([
      'label' => 'Padded Slug',
      'id' => 'padded_slug',
      'settings[client_id]' => 'test_client_id',
      'settings[client_secret]' => 'test_client_secret',
      'settings[provider_slug]' => '  padded-slug  ',
    ], 'Create OpenID Connect client');
    $this->assertSession()->pageTextContains('OpenID Connect client Padded Slug has been added.');

    $client = $this->getTestClient('padded_slug');
    $this->assertSame('padded-slug', $client->getPlugin()->getProviderSlug());

    $this->drupalLogout();
    $this->assertRedirectRoute('padded-slug', 403);
  }

  /**
   * Tests that a stored unusable slug leaves the client form usable.
   *
   * Validation only guards the form, so an unusable slug can still arrive
   * through a configuration import or from a site that was configured before
   * the validation existed. The edit form is the only way to correct it, so it
   * has to keep rendering.
   *
   * @see https://www.drupal.org/project/openid_connect/issues/3359789
   */
  public function testStoredUnusableSlugKeepsFormUsable(): void {
    $client = $this->createTestClient('legacy_slug', 'Legacy Slug');
    $settings = $client->get('settings') + $client->getPlugin()->defaultConfiguration();
    $settings['client_id'] = 'test_client_id';
    $settings['provider_slug'] = 'acm/idm';
    $client->set('settings', $settings);
    $client->save();

    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // The redirect URL can not be built, but the form still renders and says
    // why instead of dying with an InvalidParameterException.
    $this->drupalGet('/admin/config/people/openid-connect/legacy_slug/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The provider slug acm/idm can not be used in a URL.');

    // And the slug can be corrected from that same form.
    $this->submitForm([
      'settings[client_id]' => 'test_client_id',
      'settings[client_secret]' => 'test_client_secret',
      'settings[provider_slug]' => 'acm-idm',
    ], 'Save');
    $this->assertSession()->pageTextContains('OpenID Connect client Legacy Slug has been updated.');

    $storage = \Drupal::entityTypeManager()->getStorage('openid_connect_client');
    $storage->resetCache(['legacy_slug']);
    $this->assertSame('acm-idm', $storage->load('legacy_slug')->getPlugin()->getProviderSlug());

    $this->drupalGet('/admin/config/people/openid-connect/legacy_slug/edit');
    $this->assertSession()->pageTextNotContains('can not be used in a URL');

    $this->drupalLogout();
    $this->assertRedirectRoute('acm-idm', 403);
  }

  /**
   * Tests that the provider_slug is used as the redirect_uri in the OAuth flow.
   *
   * Verifies that OpenIDConnectClientBase::getRedirectUrl() uses the slug when
   * one is configured, so the redirect_uri sent to the authorization server
   * matches what the provider expects.
   */
  public function testProviderSlugUsedAsRedirectUri(): void {
    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Create a client with a custom provider_slug.
    $this->drupalGet('/admin/config/people/openid-connect/add/generic');
    $this->submitForm([
      'label' => 'Slug Flow Test',
      'id' => 'slug_flow_client',
      'settings[client_id]' => 'test_client_id',
      'settings[client_secret]' => 'test_client_secret',
      'settings[provider_slug]' => 'my-custom-slug',
    ], 'Create OpenID Connect client');
    $this->assertSession()->pageTextContains('OpenID Connect client Slug Flow Test has been added.');

    // Enable autostart so /user/login triggers the authorize redirect,
    // matching the pattern used in AutoLoginTest::testAutoRedirect().
    $this->drupalGet('/admin/config/people/openid-connect/settings');
    $this->submitForm([
      'autostart_login' => 1,
      'user_login_display' => 'below',
    ], 'Save configuration');

    // Trigger the OAuth authorization flow as an anonymous user.
    $this->drupalLogout();
    $this->drupalGet('/user/login');

    // The authorize redirect URL must include the slug as the redirect_uri,
    // not the entity ID ('slug_flow_client').
    $authorizeUrl = $this->getSession()->getCurrentUrl();
    $this->assertStringContainsString('openid-connect/my-custom-slug', $authorizeUrl);
    $this->assertStringNotContainsString('openid-connect/slug_flow_client', $authorizeUrl);
  }

  /**
   * Helper function to assert a route has an expected status code.
   *
   * @param string $slug
   *   The provider slug to test in the redirect URL.
   * @param int $code
   *   The expected HTTP status code.
   */
  protected function assertRedirectRoute(string $slug, int $code): void {
    $this->drupalGet(sprintf('/openid-connect/%s', $slug));
    // Access denied means it exists at the custom slug.
    $this->assertSession()->statusCodeEquals($code);
  }

  /**
   * Helper function to assert the initiate route resolves a slug or ID.
   *
   * The initiate route is used for ISS-initiated SSO and must be able to
   * resolve a client by its provider slug, not only its machine name.
   *
   * @param string $value
   *   The provider slug or entity ID to test in the initiate URL.
   * @param int $code
   *   The expected HTTP status code.
   */
  protected function assertInitiateRoute(string $value, int $code): void {
    $this->drupalGet(sprintf('/openid-connect/%s/initiate', $value));
    // Access denied means it resolves to an existing client.
    $this->assertSession()->statusCodeEquals($code);
  }

}
