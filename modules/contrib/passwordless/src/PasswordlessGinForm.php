<?php

namespace Drupal\passwordless;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form-related hook implementations for compatibility with gin_login module.
 */
class PasswordlessGinForm {

  /**
   * Implements hook_form_alter().
   */
  public function alter(&$form, FormStateInterface $form_state, $form_id) {
    switch ($form_id) {
      case 'passwordless_login':
        // Pretend it's a core login form so gin_login can do its thing.
        gin_login_form_alter($form, $form_state, 'user_login');
        $form['more-links']['passwordless_help_link'] = $form['passwordless_help_link'];
        unset($form['passwordless_help_link'], $form['more-links']['forgot_password_link']);
        break;
    }
  }

}
