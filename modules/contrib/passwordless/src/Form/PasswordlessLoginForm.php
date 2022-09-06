<?php

namespace Drupal\passwordless\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Form\UserPasswordForm;

/**
 * Provides a user password reset form for passwordless login.
 */
class PasswordlessLoginForm extends UserPasswordForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'passwordless_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('passwordless.settings');
    $form = parent::buildForm($form, $form_state);

    $form['name']['#type'] = 'email';
    $form['name']['#title'] = $this->t('Email address');
    $form['mail']['#markup'] = $this->t('A login link will be sent to your registered email address.');
    $form['actions']['submit']['#value'] = $this->t('Get a login link', [], ['context' => 'passwordless_login_form']);

    if (!empty($config->get('passwordless_show_help'))) {
      $form['passwordless_help_link'] = [
        '#title' => $this->t($config->get('passwordless_help_link_text')),
        '#type' => 'link',
        '#url' => Url::fromRoute('passwordless.help'),
        '#attributes' => [
          'id' => 'passwordless-help-link',
          'rel' => 'nofollow',
        ],
        '#weight' => 1000,
      ];

      if (!empty($config->get('passwordless_add_css'))) {
        $form['passwordless_help_link']['#attached'] = ['library' => ['passwordless/passwordless.login']];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Identical to \Drupal\user\Form\UserPasswordForm::validateForm()
   * except for the error message.
   *
   * Difference from 7.x version: despite the form label, it will validate
   * user names as well as email addresses.
   *
   * @todo Add multiple_email support when module is available.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('name'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties([
      'mail' => $name,
      'status' => '1',
    ]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties([
        'name' => $name,
        'status' => '1',
      ]);
    }
    $account = reset($users);
    if ($account && $account->id()) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }
    else {
      $form_state->setErrorByName('name', $this->t('Sorry, %name is not recognized as an active email address on this website.', ['%name' => $name]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('passwordless.settings');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $redirect = 'user.page';

    $account = $form_state->getValue('account');
    // Mail one time login URL and instructions using current language.
    $mail = _user_mail_notify('password_reset', $account, $langcode);
    if (!empty($mail)) {
      $this->logger('passwordless')
        ->notice('Login link mailed to %name at %email.', [
          '%name' => $account->getDisplayName(),
          '%email' => $account->getEmail(),
        ]);

      if (!empty($config->get('passwordless_toggle_sent_page'))) {
        $redirect = 'passwordless.user_login_sent';
      }
      else {
        $this->messenger()
          ->addMessage(t('The login link has been sent to your email address.'));
      }
    }

    $form_state->setRedirect($redirect);
  }
}
