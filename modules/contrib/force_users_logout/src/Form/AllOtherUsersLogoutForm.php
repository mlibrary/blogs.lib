<?php

namespace Drupal\force_users_logout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to log out all other users.
 */
class AllOtherUsersLogoutForm extends ConfigFormBase {

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
      'force_users_logout.allotherusers_logout_form',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'allotherusers_force_logout_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('force_users_logout.allotherusers_logout_form');
    $form = [];
    $roles = Role::loadMultiple();
    $optionarray[] = '';
    foreach ($roles as $rolekey => $roleval) {
      if ($rolekey != 'administrator' && $rolekey != 'authenticated' && $rolekey != 'anonymous') {
        $optionsarray[$rolekey] = $roleval->label();
      }
    }
    $form['input_fields']['selectallogout'] = [
      '#type' => 'checkbox',
      '#options' => ["otherusers" => "Logout users other than admin"],
      '#title' => $this->t('This setting will force logout all users except admin'),
      '#default_value' => $config->get('selectallogout'),
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
    $selectedroles = $form_state->getValue('selectallogout');
    if (!empty($selectedroles)) {
      $query = \Drupal::entityQuery('user')
        ->condition('status', 1)
        ->condition('roles', 'administrator', '<>')
        ->condition('roles', 'anonymous', '<>');
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
      $this->messenger()->addStatus('All the users except admin role are logged out');
    }
  }

}
