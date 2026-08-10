<?php

namespace Drupal\autoban\Form;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\autoban\Controller\AutobanController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Autoban rules deleting.
 */
class AutobanDeleteAllForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The autoban object.
   *
   * @var \Drupal\autoban\Controller\AutobanController
   */
  protected $autoban;

  /**
   * Construct the AutobanFormBase.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\autoban\Controller\AutobanController $autoban
   *   Autoban object.
   */
  public function __construct(EntityTypeManager $entity_type_manager, AutobanController $autoban) {
    $this->entityTypeManager = $entity_type_manager;
    $this->autoban = $autoban;
  }

  /**
   * Factory method for AutobanFormBase.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('autoban')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoban_delete_all_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $controller = $this->autoban;

    $form['rule_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Rule type'),
      '#default_value' => 0,
      '#options' => $controller->ruleTypeList(),
    ];

    $form['type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Type'),
      '#maxlength' => 255,
    ];

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message pattern'),
      '#maxlength' => 255,
    ];

    $form['referer'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Referrer pattern'),
      '#maxlength' => 255,
    ];

    $thresholds_config = $this->config('autoban.settings')->get('autoban_thresholds');
    $thresholds = !empty($thresholds_config) ?
    explode(PHP_EOL, $thresholds_config)
    : [1, 2, 3, 5, 10, 20, 50, 100];
    $thresholds_options = [0 => $this->t('All')] + array_combine($thresholds, $thresholds);

    $form['threshold'] = [
      '#type' => 'select',
      '#title' => $this->t('Threshold'),
      '#options' => $thresholds_options,
    ];

    $windows_config = $this->config('autoban.settings')->get('autoban_windows');
    $windows = !empty($windows_config) ?
      explode(PHP_EOL, $windows_config)
      : ['1 hour ago', '1 day ago', '1 week ago', '1 month ago', '1 year ago'];
    $windows_options = [0 => $this->t('All')] + array_combine($windows, $windows);

    $form['window'] = [
      '#type' => 'select',
      '#title' => $this->t('Window'),
      '#options' => $windows_options,
    ];

    $form['user_type'] = [
      '#type' => 'select',
      '#title' => $this->t('User type'),
      '#default_value' => 0,
      '#options' => $controller->userTypeList(),
    ];

    $providers = [];
    $banManagerList = $controller->getBanProvidersList();
    if (!empty($banManagerList)) {
      foreach ($banManagerList as $id => $item) {
        $providers[$id] = $item['name'];
      }
    }

    $form['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('IP ban provider'),
      '#options' => [0 => $this->t('All')] + $providers,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete all'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Criterions make.
    $criterions = [];
    $values = $form_state->getValues();

    $type = trim($values['type']);
    if (!empty($type)) {
      $criterions['type'] = $type;
    }

    $message = trim($values['message']);
    if (!empty($message)) {
      $criterions['message'] = $message;
    }

    $referer = trim($values['referer']);
    if (!empty($referer)) {
      $criterions['referer'] = $referer;
    }

    $threshold = $values['threshold'];
    if ($threshold > 0) {
      $criterions['threshold'] = $threshold;
    }

    $window = $values['window'];
    if (!empty($window)) {
      $criterions['window'] = $window;
    }

    $user_type = $values['user_type'];
    if ($user_type > 0) {
      $criterions['user_type'] = $user_type;
    }

    $rule_type = $values['rule_type'];
    if ($rule_type > 0) {
      $criterions['rule_type'] = $rule_type;
    }

    $provider = $values['provider'];
    if (!empty($provider)) {
      $criterions['provider'] = $provider;
    }

    $autoban_entity = $this->entityTypeManager->getStorage('autoban');
    $ids = $autoban_entity->loadByProperties($criterions);
    if (!empty($ids)) {
      $autoban_entity->delete($ids);
      $this->messenger()->addMessage($this->t('Rules deleted: @count', ['@count' => count($ids)]));
    }
    else {
      $this->messenger()->addMessage($this->t('No rules deleted'), 'warning');
    }

    $form_state->setRedirect('entity.autoban.list');
  }

}
