<?php

namespace Drupal\passwordless\Controller;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\user\Controller\UserController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides core's UserController methods.
 */
class PasswordlessUserController extends UserController {

  /**
   * {@inheritDoc}
   *
   * @see \Drupal\Core\EventSubscriber\RedirectResponseSubscriber::checkRedirectUrl()
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
    $response = parent::resetPass($request, $uid, $timestamp, $hash);

    if ($destination = $request->query->get('destination')) {
      // Remove destination from request to prevent it from taking precedence.
      $request->query->replace(['destination' => '']);
      // Rebuild the response.
      $response = $this->redirect(
        'user.reset.form',
        ['uid' => $uid],
        ['query' => ['destination' => $destination]],
      );
    }

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function resetPassLogin($uid, $timestamp, $hash, Request $request) {
    $redirect = parent::resetPassLogin($uid, $timestamp, $hash, $request);
    // This is pretty safe, since at this point there shouldn't be any other
    // status messages.
    $this->messenger()->deleteByType(MessengerInterface::TYPE_STATUS);
    // We don't want to tell the user to set their password.
    $this->messenger()->addStatus($this->t('You have just used your one-time login link. It is no longer necessary to use this link to log in.'));
    return $redirect;
  }

}
