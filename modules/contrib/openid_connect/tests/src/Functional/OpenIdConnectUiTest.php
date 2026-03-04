<?php

namespace Drupal\Tests\openid_connect\Functional;

/**
 * Functional test for openid connect clients.
 *
 * @todo Improve these.
 *
 * @group openid_connect
 */
class OpenIdConnectUiTest extends OpenIdConnectTestBase {

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
   * Tests the client list.
   */
  public function testClientList() {
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('There are no openid connect client entities yet.');
  }

  /**
   * Test route permissions on the client enable/disable routes.
   */
  public function testEnableDisableClient(): void {
    $this->createTestClient('test_oidc_label', 'Test OIDC Client');
    // Confirm route permissions.
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/enable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/disable');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['administer openid connect clients']);
    $this->drupalLogin($account);

    // Confirm CSRF protection.
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/enable');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/config/people/openid-connect/test_oidc_label/disable');
    $this->assertSession()->statusCodeEquals(403);

    // Confirm the UI works through the admin form.
    $this->drupalGet('/admin/config/people/openid-connect');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Disable');
    $this->clickLink('Disable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Enable');
    $this->clickLink('Enable');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Disable');
  }

  /**
   * Tests that admin can create a user account and set the password for it.
   */
  public function testAdminCreateUser(): void {
    $admin = $this->createUser(['administer users']);
    // Create fake external account linked to the admin account.
    $this->container->get('externalauth.authmap')->save($admin, 'test_provider', $this->randomString());

    // Confirm #3357538 that and administrator can create
    // new passwords for new accounts.
    $this->drupalLogin($admin);
    $this->drupalGet('/admin/people/create');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Add user');
    $this->assertSession()->fieldExists('Password');

    // Confirm #3357538 that the administrator can view
    // the password field of an existing user.
    $account = $this->createUser();
    $this->drupalGet("/user/{$account->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Password');

    // Confirm an administrator can not see their own password
    // They need the set own password permission.
    $this->drupalGet("/user/{$admin->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('Password');
    // Logout as the administrator.
    $this->drupalLogout();
    $this->drupalResetSession();

    // Login as a new administrator who can manage their own password.
    $newAdmin = $this->createUser([
      'administer users',
      'openid connect set own password',
    ]);
    // Link the new admin user to an external account.
    $this->container->get('externalauth.authmap')->save($newAdmin, 'test_provider', $this->randomString());
    $this->drupalLogin($newAdmin);
    $this->drupalGet("/user/{$newAdmin->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Password');
    // Logout as the new administrator.
    $this->drupalLogout();
    $this->drupalResetSession();

    // Login as the normal user and confirm they can see their
    // password field.
    $this->drupalLogin($account);
    $this->drupalGet("/user/{$account->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Password');

    // Link the normal user to an external account.
    $this->container->get('externalauth.authmap')->save($account, 'test_provider', $this->randomString());
    // Confirm the user can no longer access their password field.
    $this->drupalGet("/user/{$account->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('Password');
    $this->drupalLogout();
    $this->drupalResetSession();

    // Link a new account with permission to set a password.
    // Confirm they are able to see the password field.
    $newAccount = $this->createUser(['openid connect set own password']);
    $this->container->get('externalauth.authmap')->save($newAccount, 'test_provider', $this->randomString());
    $this->drupalLogin($newAccount);
    $this->drupalGet("/user/{$newAccount->id()}/edit");
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Password');
    $this->drupalLogout();
    $this->drupalResetSession();
  }

}
