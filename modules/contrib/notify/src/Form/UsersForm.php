<?php

namespace Drupal\notify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\notify\NotifyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class UsersForm extends ConfigFormBase {

  /**
   * The notify service.
   *
   * @var \Drupal\notify\NotifyInterface
   */
  protected $notify;

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
   * @param \Drupal\notify\NotifyInterface $notify
   *   The notify service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, NotifyInterface $notify, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->notify = $notify;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('notify'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_users';
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
    $db_connection = \Drupal::database();
    $form['#tree'] = TRUE;
    $form['info'] = [
      '#markup' => '<p>' . $this->t('The following table shows all users that have notifications enabled:') . '</p>',
    ];

    // Fetch users with notify enabled.
    $q = $db_connection->select('notify', 'n');
    $q->join('users', 'u', 'n.uid = u.uid');
    $q->join('users_field_data', 'v', 'n.uid = v.uid');
    $q->fields('v', ['uid', 'name', 'mail', 'langcode']);
    $q->fields('n', ['status', 'node', 'comment', 'attempts']);
    $q->condition('n.status', 1);
    $q->condition('v.status', 1);
    $q->orderBy('v.name');
    $uresult = $q->execute();

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('List of users'),
    ];

    $form['settings']['table'] = [
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => [
        $this->t('Username'),
        $this->t('E-mail Address'),
        $this->t('Content'),
        $this->t('Comment'),
        $this->t('Failed Attempts'),
      ],
      '#id' => 'notify_settings_table',
    ];

    foreach ($uresult as $user) {
      $form['settings']['table'][$user->uid]['username'] = [
        '#markup' => $user->name,
      ];
      $form['settings']['table'][$user->uid]['mail'] = [
        '#markup' => $user->mail,
      ];
      $form['settings']['table'][$user->uid]['node'] = [
        '#type' => 'checkbox',
        '#default_value' => $user->node,
      ];
      $form['settings']['table'][$user->uid]['comment'] = [
        '#type' => 'checkbox',
        '#default_value' => $user->comment,
      ];
      $form['settings']['table'][$user->uid]['attempts'] = [
        '#markup' => $user->attempts ? intval($user->attempts) : 0,
      ];
    }

    $form['info2'] = [
      '#markup' => '<p>' . $this->t("You may check/uncheck the checkboxes to change the users' subscription. Press “Save settings” to save the settings.") . '</p>',
    ];

    $form['bulk'] = [
      '#title' => $this->t('Bulk-subscribe all unsubscribed users'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
      '#description' => $this->t('Apply “Default Settings” to <em>all</em> non-blocked users that do not already subscribe to notifications. Users that already has enabled “Receive email notifications” under their “Notify Settings” will not be affected.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!empty($values['bulk'])) {
      $this->notify->bulkSubscribeUsers();
    }
    elseif (!array_key_exists('settings', $values)) {
      $this->messenger->addMessage($this->t('No users have notifications enabled.'), 'warning');
      return;
    }

    if (isset($values['settings']['table']) && $values['settings']['table']) {
      foreach ($values['settings']['table'] as $uid => $settings) {
        $this->notify->setUserNotify($uid, [
          'node' => $settings['node'],
          'comment' => $settings['comment'],
        ]);
      }
    }
    $this->messenger->addMessage($this->t('Users notify settings saved.'));
  }

}
