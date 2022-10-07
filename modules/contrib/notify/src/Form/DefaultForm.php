<?php

namespace Drupal\notify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class DefaultForm extends ConfigFormBase {

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Drupal\Core\Extension\ModuleHandler definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
    MessengerInterface $messenger,
    ModuleHandler $module_handler) {
    parent::__construct($config_factory);
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('messenger'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_default_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'notify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('notify.settings');
    $set = 'defaults';
    $form['notify_preamble'] = [
      '#markup' => $this->t('<p>The settings on this page will only apply to <em>new</em> users. Changing these defaults will not affect existing users.<p>'),
    ];
    $form['notify_defaults'] = [
      '#type' => 'details',
      '#title' => $this->t('Notification default for new users'),
      '#open' => TRUE,
      '#description' => $this->t('The default master switch for new users (check for enabled, uncheck for disabled).'),
    ];

    $form['notify_defaults']['notify_reg_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Receive email notifications'),
      '#return_value' => 1,
      '#default_value' => $config->get('notify_reg_default'),
    ];

    $form['notify_defs'] = [
      '#type' => 'details',
      '#title' => $this->t('Initial settings'),
      '#open' => TRUE,
      '#description' => $this->t('These are the initial settings that will apply to new users registering, and to users that are enrolled in notifications with batch subscription.'),
    ];
    $form['notify_defs']['node'] = [
      '#type' => 'radios',
      '#title' => $this->t('Notify new content'),
      '#default_value' => $config->get('notify_def_node'),
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#description' => $this->t('Include new posts in the notification mail.'),
    ];
    $form['notify_defs']['comment'] = [
      '#type' => 'radios',
      '#access' => $this->moduleHandler->moduleExists('comment'),
      '#title' => $this->t('Notify new comments'),
      '#default_value' => $config->get('notify_def_comment'),
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#description' => $this->t('Include new comments in the notification mail.'),
    ];

    $set = 'ntype';
    $form[$set] = [
      '#type' => 'details',
      '#title' => $this->t('Notification subscriptions by node type'),
      '#open' => TRUE,
      '#description' => $this->t('Tick the node types to make available for subscription. New users are automatically subscribed, but can unsubscribe in their user profile if they have the permission "Access Notify".'),
    ];
    $nodetypes = [];
    $node_types = \Drupal::entityTypeManager()->getStorage('node_type')->loadMultiple();
    foreach ($node_types as $type => $object) {
      $nodetypes[$type] = $object->label();
    }

    if (NULL !== ($config->get('notify_nodetypes'))) {
      $def_nodetypes = $config->get('notify_nodetypes');
    }
    else {
      $def_nodetypes = [];
    }

    $form[$set]['notify_nodetypes'] = [
      '#type' => 'checkboxes',
      '#title' => 'Node types',
      '#options' => $nodetypes,
      '#default_value' => $def_nodetypes,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('notify.settings')
      ->set('notify_reg_default', $values['notify_reg_default'])
      ->set('notify_def_node', $values['node'])
      ->set('notify_def_comment', $values['comment'])
      ->set('notify_nodetypes', $values['notify_nodetypes'])
      ->save();
    $this->messenger->addMessage($this->t('Notify default settings saved.'));
  }

}
