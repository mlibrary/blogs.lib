<?php

namespace Drupal\force_users_logout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to log out users based on their roles.
 */
class RoleBasedLogoutForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs entity type manager service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'force_users_logout.rolebased_logout_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rolebased_force_logout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $roles = Role::loadMultiple();
    $optionarray[] = '';
    foreach ($roles as $rolekey => $roleval) {
      if ($rolekey != 'administrator' && $rolekey != 'authenticated' && $rolekey != 'anonymous') {
        $optionsarray[$rolekey] = $roleval->label();
      }
    }
    $form['input_fields']['selectrole'] = [
      '#type' => 'checkboxes',
      '#options' => $optionsarray ?? [],
      '#title' => $this->t('Select the roles'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $selectedroles = array_filter($form_state->getValue('selectrole'));
    foreach ($selectedroles as $selectedrole) {
      $query = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', $selectedrole);
      $uids = $query->accessCheck()->execute();
      $result = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids);
      /** @var \Drupal\user\UserInterface $user */
      foreach ($result as $user) {
        \Drupal::currentUser()->setAccount($user);
        if (\Drupal::currentUser()->isAuthenticated()) {
          $session_manager = \Drupal::service('session_manager');
          $session_manager->delete(\Drupal::currentUser()->id());
        }
      }
    }
    $this->messenger()->addStatus('The users belonging to the selected roles have been Logged out.');
  }

}
