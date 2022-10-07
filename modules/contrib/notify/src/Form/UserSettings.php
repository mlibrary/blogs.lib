<?php

namespace Drupal\notify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\notify\NotifyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class UserSettings extends ConfigFormBase {

  /**
   * The notify service.
   *
   * @var \Drupal\notify\NotifyInterface
   */
  protected $notify;

  /**
   * The core messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new UserSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\notify\NotifyInterface $notify
   *   The notify service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   * @param \\Drupal\Core\Routing\CurrentRouteMatch $route_match
   *   The route match service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, NotifyInterface $notify, MessengerInterface $messenger, RouteMatchInterface $route_match) {
    parent::__construct($config_factory);
    $this->notify = $notify;
    $this->messenger = $messenger;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('notify'),
      $container->get('messenger'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_user_settings';
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
    $db_connection = \Drupal::Database();
    $entityManager = \Drupal::entityTypeManager();
    $module_handler = \Drupal::service('module_handler');
    $userprofile = \Drupal::routeMatch()->getParameter('user');

    $account = $entityManager->getStorage('user')->load($userprofile);
    if (!is_object($account)) {
      return;
    }

    // @todo can this be moved to the notify service class?
    $result = $db_connection->select('users', 'u');
    $result->leftjoin('users_field_data', 'v', 'u.uid = v.uid');
    $result->leftjoin('notify', 'n', 'u.uid = n.uid');
    $result->fields('u', ['uid']);
    $result->fields('v', ['name', 'mail']);
    $result->fields('n', ['node', 'comment', 'status']);
    $result->condition('u.uid', $userprofile);
    $result->allowRowCount = TRUE;
    $notify = $result->execute()->fetchObject();

    // Internal error.
    if (!is_object($notify)) {
      $notify = NULL;
    }

    $form = [];
    if (!$notify->mail) {
      $url = '/user/' . $userprofile . '/edit';
      $this->messenger->addMessage($this->t('Your e-mail address must be specified on your <a href="@url">my account</a> page.', ['@url' => $url]), 'error');
    }

    $form['notify_page_master'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Master switch'),
    ];
    // If user existed before notify was enabled, these are not set in db.
    if (!isset($notify->status)) {
      $notify->status = 0;
      $notify->node = 0;
      $notify->comment = 0;
    }

    $form['notify_page_master']['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Receive email notifications'),
      '#default_value' => $notify->status,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#description' => $this->t('The Master switch overrides all other settings for Notify.  You can use it to disable email notifications without having to disturb any of your settings under “Detailed settings” and “Subscriptions”.'),
    ];
    $form['notify_page_detailed'] = [
      '#type' => 'details',
      '#title' => $this->t('Detailed settings'),
      '#open' => TRUE,
      '#description' => $this->t('These settings will only be effective if the master switch is set to “Enabled”.'),
    ];
    $form['notify_page_detailed']['node'] = [
      '#type' => 'radios',
      '#title' => $this->t('Notify new content'),
      '#default_value' => $notify->node,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#description' => $this->t('Include new posts in the notification mail.'),
    ];
    $form['notify_page_detailed']['comment'] = [
      '#type' => 'radios',
      '#access' => $module_handler->moduleExists('comment'),
      '#title' => $this->t('Notify new comments'),
      '#default_value' => $notify->comment,
      '#options' => [$this->t('Disabled'), $this->t('Enabled')],
      '#description' => $this->t('Include new comments in the notification mail.'),
    ];
    $set = 'notify_page_nodetype';
    $form[$set] = [
      '#type' => 'details',
      '#title' => $this->t('Subscriptions'),
      '#open' => FALSE,
      '#description' => $this->t('Tick the node types you want to subscribe to.'),
    ];
    $alltypes = $entityManager->getStorage('node_type')->loadMultiple();
    $enatypes = [];

    foreach ($alltypes as $type => $object) {
      if ($config->get(NotifyInterface::NODE_TYPE . $type)) {
        $enatypes[] = [$type, $object->label()];
      }
    }
    if ($account->hasPermission('administer notify queue') || empty($enatypes)) {
      $enatypes = [];
      foreach ($alltypes as $type => $obj) {
        $enatypes[] = [$type, $obj->label()];
      }
    }

    // Get the permitted subscriptions.
    if (NULL !== ($config->get('notify_nodetypes'))) {
      $def_nodetypes = $config->get('notify_nodetypes');
    }
    else {
      $def_nodetypes = [];
    }

    // Get user's subscriptions.
    // @todo can the database call be moved to the notify service class?
    foreach ($enatypes as $type) {
      $field = $db_connection->select('notify_subscriptions', 'n')
        ->fields('n', ['uid', 'type'])
        ->condition('uid', $userprofile)
        ->condition('type', $type[0])
        ->execute()->fetchObject();
      // Only show those permitted or already subscribed.
      if ((isset($def_nodetypes[$type[0]]) && $def_nodetypes[$type[0]]) || $field) {
        $default = $field ? TRUE : FALSE;
        $form[$set][NotifyInterface::NODE_TYPE . $type[0]] = [
          '#type' => 'checkbox',
          '#title' => $type[1],
          '#return_value' => 1,
          '#default_value' => $default,
        ];
      }
    }

    $form['uid'] = [
      '#type' => 'value',
      '#value' => $userprofile,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->notify->setUserNotify($values['uid'], $values);
    $this->messenger->addMessage($this->t('Notify settings saved.'));
  }

}
