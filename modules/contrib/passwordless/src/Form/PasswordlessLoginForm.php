<?php

namespace Drupal\passwordless\Form;

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
    $config = $this->config('passwordless.settings');
    $form = parent::buildForm($form, $form_state);
    // Pretend this is the core login form.
    $form['#attributes']['class'][] = 'user-login-form';

    $form['name']['#type'] = 'email';
    $form['name']['#title'] = $this->t('Email address');
    $form['mail']['#markup'] = $this->t('A login link will be sent to your registered email address.');
    $form['actions']['submit']['#value'] = $this->t('Get a login link', [], ['context' => 'passwordless_login_form']);

    if (!empty($config->get('passwordless_show_help'))) {
      $form['passwordless_help_link'] = [
        '#title' => $config->get('passwordless_help_link_text'),
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('passwordless.settings');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $redirect = 'user.page';

    if ($account = $form_state->getValue('account')) {
      // Mail one-time login URL and instructions using current language.
      $mail = _user_mail_notify('password_reset', $account, $langcode);
      if (!empty($mail)) {
        $this->logger('passwordless')
          ->notice('Login link mailed to %name at %email.', [
            '%name' => $account->getDisplayName(),
            '%email' => $account->getEmail(),
          ]);
      }
    }
    else {
      $this->logger('user')
        ->info('Passwordless-login form was submitted with an unknown or inactive account: %name.', [
          '%name' => $form_state->getValue('name'),
        ]);
    }

    if (!empty($config->get('passwordless_toggle_sent_page'))) {
      $redirect = 'passwordless.user_login_sent';
    }
    else {
      // Make sure the status text is displayed even if no email was sent. This
      // message is deliberately the same as the success message for privacy.
      $this->messenger()
        ->addStatus($this->t('If %identifier is a valid account, an email will be sent with a login link.', [
          '%identifier' => $form_state->getValue('name'),
        ]));
    }

    $form_state->setRedirect($redirect);
  }

}
