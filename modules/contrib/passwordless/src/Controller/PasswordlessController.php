<?php

namespace Drupal\passwordless\Controller;

use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\passwordless\PasswordlessTextInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for the Passwordless module.
 */
class PasswordlessController extends ControllerBase {

  /**
   * The passwordless.text service.
   *
   * @var \Drupal\passwordless\PasswordlessTextInterface
   */
  protected $passwordlessText;

  /**
   * PasswordlessController constructor.
   *
   * @param \Drupal\passwordless\PasswordlessTextInterface $passwordless_text
   *   The passwordless.text service.
   */
  public function __construct(PasswordlessTextInterface $passwordless_text) {
    $this->passwordlessText = $passwordless_text;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('passwordless.text')
    );
  }

  /**
   * Returns the help page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function helpPage() {
    return [
      '#markup' => $this->passwordlessText->get('help_text'),
    ];
  }

  /**
   * Returns the help page title.
   *
   * @return string
   *   The page title, retrieved from settings.
   */
  public function helpPageTitle() {
    return $this->passwordlessText->get('help_link_text');
  }

  /**
   * Checks access to the help page.
   *
   * This is based on whether the current user can access content, and whether
   * the help page is enabled in settings.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function helpPageAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->hasPermission('access content') && !empty($this->getConfig()
      ->get('passwordless_show_help')));
  }

  /**
   * Returns the content of the confirmation page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function sentPage() {
    $http_referrer = UrlHelper::filterBadProtocol($_SERVER['HTTP_REFERER']);

    if (
      !$this->currentUser()
        ->hasPermission('configure passwordless settings') &&
      $http_referrer != Url::fromRoute('user.page', [], ['absolute' => TRUE])->toString() &&
      $http_referrer != Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString()
    ) {
      return new RedirectResponse(Url::fromRoute('user.page')->toString());
    }
    else {
      return [
        '#markup' => $this->passwordlessText->get('sent_page_text'),
      ];
    }
  }

  /**
   * Returns the confirmation page title.
   *
   * @return string
   *   The page title, retrieved from settings.
   */
  public function sentPageTitle() {
    return $this->passwordlessText->get('sent_title_text');
  }

  /**
   * Checks access to the confirmation page.
   *
   * Based on whether the current user is anonymous, or has permission to
   * configure the module.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   The current user.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function sentPageAccess(AccountInterface $account) {
    return AccessResult::allowedIf($account->isAnonymous() || $account->hasPermission('configure passwordless settings'));
  }

  /**
   * Overrides the user.pass route and redirects to user.page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function redirectUserPassPage() {
    return new RedirectResponse(Url::fromRoute('user.page')->toString());
  }

  /**
   * Returns Passwordless settings.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  public function getConfig() {
    return parent::config('passwordless.settings');
  }

}
