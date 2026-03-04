<?php

declare(strict_types=1);

namespace Drupal\openid_connect\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\Service\LogoutService;
use Drupal\user\Form\UserLogoutConfirm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide a user logout confirmation form for OpenID logouts.
 */
final class UserLogoutConfirmation extends UserLogoutConfirm {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('Drupal\openid_connect\Service\LogoutService')
    );
  }

  /**
   * Constructor for the UserLogoutConfirmation form.
   */
  public function __construct(
    protected readonly LogoutService $logoutService,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function getFormId(): string {
    return 'openid_connect_user_logout';
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the response prior to logging the user out.
    $response = $this->logoutService->getLogoutRedirectResponse();
    user_logout();
    // Get the expected OpenID Redirect.
    $form_state->setResponse($response);
  }

}
