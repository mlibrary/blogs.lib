<?php

namespace Drupal\notify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\notify\NotifyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_admin_settings';
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

    $period = [
      300 => \Drupal::service('date.formatter')->formatInterval(300),
      600 => \Drupal::service('date.formatter')->formatInterval(600),
      900 => \Drupal::service('date.formatter')->formatInterval(900),
      1800 => \Drupal::service('date.formatter')->formatInterval(1800),
      3600 => \Drupal::service('date.formatter')->formatInterval(3600),
      10800 => \Drupal::service('date.formatter')->formatInterval(10800),
      21600 => \Drupal::service('date.formatter')->formatInterval(21600),
      43200 => \Drupal::service('date.formatter')->formatInterval(43200),
      86400 => \Drupal::service('date.formatter')->formatInterval(86400),
      172800 => \Drupal::service('date.formatter')->formatInterval(172800),
      259200 => \Drupal::service('date.formatter')->formatInterval(259200),
      604800 => \Drupal::service('date.formatter')->formatInterval(604800),
      1209600 => \Drupal::service('date.formatter')->formatInterval(1209600),
      2419200 => \Drupal::service('date.formatter')->formatInterval(2419200),
      NotifyInterface::PERIOD_NEVER => $this->t('Never'),
    ];

    $attempts = [
      0 => $this->t('Disabled'),
      1 => 1,
      2 => 2,
      3 => 3,
      4 => 4,
      5 => 5,
      6 => 6,
      7 => 7,
      8 => 8,
      9 => 9,
      10 => 10,
      15 => 15,
      20 => 20,
    ];

    $batch = [
      2 => 2,
      3 => 3,
      10 => 10,
      20 => 20,
      50 => 50,
      100 => 100,
      200 => 200,
      400 => 400,
    ];

    $form['notify_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('E-mail notification settings'),
      '#open' => TRUE,
    ];

    $form['notify_settings']['notify_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Send notifications every'),
      '#default_value' => $config->get('notify_period'),
      '#options' => $period,
      '#description' => $this->t('How often should new content notifications be sent? Requires cron to be running at least this often.'),
    ];

    $form['notify_settings']['notify_send_hour'] = [
      '#type' => 'select',
      '#title' => $this->t('Hour to Send Notifications'),
      '#description' => $this->t('Specify the hour (24-hour clock) in which notifications should be sent, if the frequency is one day or greater.'),
      '#default_value' => $config->get('notify_send_hour'),
      '#options' => [
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9,
        10, 11, 12, 13, 14, 15, 16, 17, 18, 19,
        20, 21, 22, 23,
      ],
    ];

    $form['notify_settings']['notify_attempts'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of failed sends after which notifications are disabled'),
      '#default_value' => $config->get('notify_attempts'),
      '#options' => $attempts,
    ];

    $form['notify_settings']['notify_batch'] = [
      '#type' => 'select',
      '#title' => $this->t('Maximum number of notifications to send out per cron run'),
      '#description' => $this->t('The maximum number of notification e-mails to send in each pass of  a cron maintenance task. If necessary, reduce the number of items to prevent resource limit conflicts.'),
      '#default_value' => $config->get('notify_batch'),
      '#options' => $batch,
    ];

    $form['notify_settings']['notify_include_updates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include updated posts in notifications'),
      '#return_value' => 1,
      '#default_value' => $config->get('notify_include_updates'),
    ];

    $form['notify_settings']['notify_unpublished'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Administrators shall be notified about unpublished content of tracked types'),
      '#return_value' => 1,
      '#default_value' => $config->get('notify_unpublished'),
    ];

    $form['notify_settings']['notify_watchdog'] = [
      '#type' => 'radios',
      '#title' => $this->t('Watchdog log level'),
      '#default_value' => $config->get('notify_watchdog'),
      '#options' => [
        $this->t('All'),
        $this->t('Failures+Summary'),
        $this->t('Failures'),
        $this->t('Nothing'),
      ],
      '#description' => $this->t('This setting lets you specify how much to log.'),
    ];

    $form['notify_settings']['notify_weightur'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Weight of notification field in user registration form'),
      '#default_value' => $config->get('notify_weightur'),
      '#size' => 3,
      '#maxlength' => 5,
      '#description' => $this->t('The weight you set here will determine the position of the notification field when it appears in the user registration form.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('notify.settings')
      ->set('notify_period', $values['notify_period'])
      ->set('notify_send_hour', $values['notify_send_hour'])
      ->set('notify_attempts', $values['notify_attempts'])
      ->set('notify_batch', $values['notify_batch'])
      ->set('notify_include_updates', $values['notify_include_updates'])
      ->set('notify_unpublished', $values['notify_unpublished'])
      ->set('notify_watchdog', $values['notify_watchdog'])
      ->set('notify_weightur', $values['notify_weightur'])
      ->save();
    $this->messenger->addMessage($this->t('Notify admin settings saved.'));
    drupal_flush_all_caches();
  }

}
