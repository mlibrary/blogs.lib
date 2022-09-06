<?php

namespace Drupal\passwordless\Controller;

use Drupal\Core\Url;
use Drupal\user\Controller\UserController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for Passwordless routes.
 */
class PasswordlessUserController extends UserController {

  /**
   * {@inheritdoc}
   */
  public function resetPass(Request $request, $uid, $timestamp, $hash) {
    $account = $this->currentUser();
    $config = $this->config('user.settings');
    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      // The current user is already logged in.
      if ($account->id() == $uid) {
        $this->messenger()
          ->addMessage($this->t('You are logged in as %user.', ['%user' => $account->getDisplayName()]));
      }
      // A different user is already logged in on the computer.
      else {
        /** @var \Drupal\user\UserInterface $reset_link_user */
        if ($reset_link_user = $this->userStorage->load($uid)) {
          $this->messenger()
            ->addMessage($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href=":logout">log out</a> and try using the link again.',
              [
                '%other_user' => $account->getDisplayName(),
                '%resetting_user' => $reset_link_user->getDisplayName(),
                ':logout' => Url::fromRoute('user.logout')->toString(),
              ]), 'warning');
        }
        else {
          // Invalid one-time link specifies an unknown user.
          $this->messenger()
            ->addMessage($this->t('The one-time login link you clicked is invalid.'), 'error');
        }
      }
      return $this->redirect('<front>');
    }
    else {
      // The current user is not logged in, so check the parameters.
      // Time out, in seconds, until login URL expires.
      $timeout = $config->get('password_reset_timeout');
      $current = \Drupal::time()->getRequestTime();
      /* @var \Drupal\user\UserInterface $user */
      $user = $this->userStorage->load($uid);
      // Verify that the user exists and is active.
      if ($user && $user->isActive()) {
        // No time out for first time login.
        if ($user->getLastLoginTime() && $current - $timestamp > $timeout) {
          $this->messenger()
            ->addMessage($this->t('You have tried to use a one-time login link that has expired. Please request a new one using the form below.'), 'warning');
          return $this->redirect('user.page');
        }
        elseif ($user->isAuthenticated() && ($timestamp >= $user->getLastLoginTime()) && ($timestamp <= $current) && ($hash === user_pass_rehash($user, $timestamp))) {
          $expiration_date = $user->getLastLoginTime() ? $this->dateFormatter->format($timestamp + $timeout) : NULL;

          /** @see Drupal\user\Form\UserController::resetPassLogin */
          user_login_finalize($user);
          $this->logger->notice('User %name used one-time login link at time %timestamp.', [
            '%name' => $user->getDisplayName(),
            '%timestamp' => $timestamp,
          ]);
          $this->messenger()
            ->addMessage($this->t('You have just used your one-time login link.'));
          $user->pass = sha1(user_password());
          $user->save();
          $route_name = 'user.page';
          $route_parameters = [];
          \Drupal::moduleHandler()
            ->alter('passwordless_login_redirect', $route_name, $route_parameters);
          return $this->redirect($route_name, $route_parameters);
        }
        else {
          $this->messenger()
            ->addMessage($this->t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'warning');
          return $this->redirect('user.page');
        }
      }
    }
    // Blocked or invalid user ID, so deny access. The parameters will be in the
    // watchdog's URL for the administrator to check.
    throw new AccessDeniedHttpException();
  }
}