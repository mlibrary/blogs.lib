<?php

namespace Drupal\force_users_logout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides a form to log out users based on the username.
 */
class IndividualUserLogoutForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'force_users_logout.individual_user_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'singleuser_force_logout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $form['input_fields']['uid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name of the user to be logged out'),
      '#autocomplete_route_name' => 'force_users_logout.autocomplete',
      '#description' => $this->t('Begin typing the username'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Force Logout'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $selecteduser = $form_state->getValue('uid');
    preg_match('#\((.*?)\)#', $selecteduser, $match);
    $uid = $match[1];
    $account = User::load($uid);
    \Drupal::currentUser()->setAccount($account);
    if (\Drupal::currentUser()->isAuthenticated()) {
      $session_manager = \Drupal::service('session_manager');
      $session_manager->delete(\Drupal::currentUser()->id());
      $this->messenger()->addStatus('The user @user has been Logged out.', ['@user' => $account->getAccountName()]);
    }
  }

}
