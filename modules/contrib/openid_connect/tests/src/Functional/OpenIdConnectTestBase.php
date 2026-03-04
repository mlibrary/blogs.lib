<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Common testing traits required for OpenID Connect.
 */
abstract class OpenIdConnectTestBase extends BrowserTestBase {

  /**
   * Override the drupalLogout() method.
   *
   * Normal logout field validation breaks when the autostart
   * setting is enabled. This override removes those assertions.
   */
  public function drupalLogout(): void {
    $destination = Url::fromRoute('user.page')->toString();
    $this->drupalGet(Url::fromRoute('user.logout.confirm', options: ['query' => ['destination' => $destination]]));
    // Target the submit button using the name rather than the value to work
    // regardless of the user interface language.
    $this->submitForm([], 'op', 'openid-connect-user-logout');
    $this->drupalResetSession();
  }

}
