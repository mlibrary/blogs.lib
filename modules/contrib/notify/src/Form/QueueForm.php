<?php

namespace Drupal\notify\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\notify\NotifyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class QueueForm extends ConfigFormBase {
  use StringTranslationTrait;

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
   * The state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\notify\NotifyInterface $notify
   *   The notify service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, NotifyInterface $notify, MessengerInterface $messenger, StateInterface $state) {
    parent::__construct($config_factory);
    $this->notify = $notify;
    $this->messenger = $messenger;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('notify'),
      $container->get('messenger'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_queue_settings';
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
   * Helper function to emulate count() prior to PHP 7.2.
   */
  private function _Count($arg) {
    return is_countable($arg) ? count($arg) : 0;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('notify.settings');

    $notify_send_last = $this->state->get('notify_send_last');
    // If notify_send_last is not set (fresh install), set it to now.
    if (!$notify_send_last) {
      $notify_send_last = \Drupal::time()->getRequestTime();
      $sendlastdate = \Drupal::service('date.formatter')->format($notify_send_last, 'short');
      $this->state->set('notify_send_last', $notify_send_last);
      \Drupal::messenger()->addMessage($this->t('Fresh install of <strong>Notify</strong>. Setting “Last notification tmestamp” to @last to avoid sending notifications about old content. You may override the timestamp to pick another time.',
        ['@last' => $sendlastdate]));
    }
    $period = $config->get('notify_period');
    $since = $notify_send_last - $period;
    $lastdate = \Drupal::service('date.formatter')->format($since, 'short');

    $cron_next = $this->state->get('notify_cron_next') ?? $this->notify->cronNext(\Drupal::time()->getRequestTime());

    // Set $start to notify_send_start from configuration.
    $start = $this->state->get('notify_send_start', 0);
    $startdate = \Drupal::service('date.formatter')->format($start, 'short');
    $next_last = $this->notify->nextNotification($notify_send_last);

    if ($next_last == -1) {
      $batch_msg = $this->t('No more notifications scheduled');
    }
    elseif ($next_last == 0) {
      $batch_msg = $this->t('The next notification is scheduled for the next cron run');
    }
    else {
      $next = \Drupal::service('date.formatter')->format($next_last, 'short');
      $batch_msg = $this->t('The next notification is scheduled for the first cron run after @next', ['@next' => $next]);
    }

    $form['process'] = [
      '#type' => 'radios',
      '#title' => $this->t('Notification queue operations'),
      '#default_value' => 0,
      '#options' => [
        $this->t('Send batch now'),
        $this->t('Truncate queue'),
        $this->t('Override timestamp'),
      ],
      '#description' => $this->t('Select “Send batch now” to send next batch of e-mails queued for notifications. Select “Truncate queue” to empty queue of pending notification <em>without</em> sending e-mails. Select “Override timestamp” to override the last notification timestamp. Press “Submit” to execute.'),
    ];

    $send_last = \Drupal::service('date.formatter')->format($notify_send_last, 'custom', 'Y-m-d H:i:s');

    $form['lastdate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last notification timestamp'),
      '#default_value' => $send_last,
      '#size' => 20,
      '#maxlength' => 19,
      '#description' => $this->t('To explicitly set the last notification timestamp, change the value of this field and select the “Override timestamp” option above, then press “Submit” to execute.'),
    ];

    $form['batch'] = [
      '#type' => 'details',
      '#title' => $this->t('Status'),
      '#description' => $this->t('<em>The values below shows the status of the</em> <strong>Notify</strong> <em>queue. Please incude these values if you post a Bug report.</em>'),
    // FALSE DEBUG.
      '#open' => TRUE,
    ];

    [$np, $cp, $nn, $cn, $nu, $cu] = $this->notify->countContent($config);
    $skipped_nodes = $this->notify->getSkippedNodes();
    $skipped_comments = $this->notify->getSkippedComments();
    // dpm($np . '+' . $cp, 'Published: np+cp');
    // dpm($nn . '+' . $cn, '??Published: nn+cn');
    // dpm($nu . '+' . $cu, 'Unpublished: nu+cu');.
    $npcp = $np + $cp;
    if ($npcp) {
      $queue_msg = $this->t('Notifications about at least @item queued', [
        '@item' => \Drupal::translation()->formatPlural($npcp, '1 post is', '@count posts are'),
      ]);
    }
    else {
      $queue_msg = $this->t('No notifications queued');
    }
    $flagcnt = $this->_Count($skipped_nodes) + $this->_Count($skipped_comments);
    if ($flagcnt) {
      $skip_msg = $this->t('@item flagged for skipping', [
        '@item' => \Drupal::translation()->formatPlural($flagcnt, '1 post is', '@count posts are'),
      ]);
    }
    else {
      $skip_msg = $this->t('No posts are flagged for skipping');
    }

    if (($np && $nu) || ($cp && $cu)) {
      $nonew_msg = '';
    }
    else {
      $nonew_msg = $this->t(', no notification about unpublished posts are queued');
    }
    if ($nu + $cu) {
      $unpub_msg = $this->t('Unpublished: @nodeup and @commup', [
        '@nodeup' => \Drupal::translation()->formatPlural($nu, '1 node', '@count nodes'),
        '@commup' => \Drupal::translation()->formatPlural($cu, '1 comment', '@count comments'),
      ]) . $nonew_msg;
    }
    else {
      $unpub_msg = $this->t('No unpublished posts');
    }

    $sent = $this->state->get('notify_num_sent');
    $fail = $this->state->get('notify_num_failed');
    $users = $this->state->get('notify_users');
    $batch_remain = $users ? count($users) : 0;

    $creat_msg = $this->t('There are @nodes and @comms created', [
      '@nodes' => \Drupal::translation()->formatPlural($np, '1 node', '@count nodes'),
      '@comms' => \Drupal::translation()->formatPlural($cp, '1 comment', '@count comments'),
    ]);
    if ($nn + $cn) {
      $publ_msg = $this->t(', and in addition @noderp and @commrp published,', [
        '@noderp' => \Drupal::translation()->formatPlural($nn, '1 node', '@count nodes'),
        '@commrp' => \Drupal::translation()->formatPlural($cn, '1 comment', '@count comments'),
      ]);
    }
    else {
      $publ_msg = '';
    }
    if ($batch_remain) {
      $intrv_msg = $this->t('between @last and @start', [
        '@last' => $lastdate,
        '@start' => $startdate,
      ]);
      $sent_msg = $this->t('Batch not yet complete.  So far @sent has been sent (@fail, @remain to go)', [
        '@sent' => \Drupal::translation()->formatPlural($sent, '1 e-mail', '@count e-mails'),
        '@fail' => \Drupal::translation()->formatPlural($fail, '1 failure', '@count failures'),
        '@remain' => \Drupal::translation()->formatPlural($batch_remain, '1 user', '@count users'),
      ]);
    }
    else {
      $intrv_msg = $this->t('since @last', [
        '@last' => $lastdate,
      ]);
      $sent_msg = $this->t('Last batch:') . ' ';
      if ($sent == 0) {
        $sent_msg = $this->t('No e-mails were sent');
      }
      else {
        $sent_msg .= $this->t('sent @sent', [
          '@sent' => \Drupal::translation()->formatPlural($sent, '1 e-mail', '@count e-mails'),
        ]);
      }
      if ($fail > 0) {
        $sent_msg .= ', ' . $this->t('@fail', [
          '@fail' => \Drupal::translation()->formatPlural($fail, '1 failure', '@count failures'),
        ]);
      }
      elseif ($sent) {
        $sent_msg .= ', ' . $this->t('no failures');
      }
    }
    $mailsystem = $config->get('mail_system') ?? NULL;
    $ms = isset($mailsystem['default-system']) ? $mailsystem['default-system'] : $this->t('system default');
    $form['batch']['schedule'] = [
      '#markup' => $creat_msg . $publ_msg . ' ' . $intrv_msg . '.<br>'
      . $unpub_msg . '.<br>'
      . $queue_msg . '.<br>'
      . $skip_msg . '.<br>'
      . $sent_msg . '.<br>'
      . $batch_msg . '.<br>'
      . $this->t('Default MailSystem: ') . $ms . '.',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('notify.settings');
    $values = $form_state->getValues();
    $db_connection = \Drupal::database();
    $process = $values['process'];
    $notify_send_last = $this->state->get('notify_send_last');
    $frform_send_last = strtotime($values['lastdate']);
    if (FALSE === $frform_send_last) {
      $this->messenger->addMessage($this->t('This does not look like a valid date format.'), 'error');
      $form_state->setRebuild();
      return;
    }
    if ($process < 2) {
      if ($notify_send_last != $frform_send_last) {
        $this->messenger->addMessage($this->t('You must select “Override timestamp” to override the timestamp.'), 'error');
        $form_state->setRebuild();
        return;
      }
    }
    elseif ($process == 2) {
      if ($notify_send_last == $frform_send_last) {
        $this->messenger->addMessage($this->t('You selected “Override timestamp”, but the timestamp is not altered.'), 'error');
        $form_state->setRebuild();
        return;
      }
    }

    $watchdog_level = $config->get('notify_watchdog') ?? 0;
    // Flush.
    if (0 == $values['process']) {
      [$num_sent, $num_fail] = $this->notify->send();

      if ($num_fail > 0) {
        $this->messenger->addMessage($this->t('@sent notification @emsent sent successfully, @fail @emfail could not be sent.',
          [
            '@sent' => $num_sent,
            '@emsent' => \Drupal::translation()->formatPlural($num_sent, 'e-mail', 'e-mails'),
            '@fail' => $num_fail,
            '@emfail' => \Drupal::translation()->formatPlural($num_fail, 'notification', 'notifications'),
          ]
        ), 'error');
      }
      elseif ($num_sent > 0) {
        $this->messenger->addMessage($this->t('@count pending notification @emails have been sent in this pass.',
        [
          '@count' => $num_sent,
          '@emails' => \Drupal::translation()->formatPlural($num_sent, 'e-mail', 'e-mails'),
        ]));
      }
      if (0 == ($num_sent + $num_fail)) {
        $this->messenger->addMessage($this->t('No notifications needed to be sent in this pass.'));
      }
      else {
        if ($watchdog_level <= 1) {
          \Drupal::logger('notify')->notice('Notifications sent: @sent, failures: @fail.',
          [
            '@sent' => $num_sent,
            '@fail' => $num_fail,
          ]);
        }
      }
      $num_sent += $this->state->get('notify_num_sent');
      $num_fail += $this->state->get('notify_num_failed');
      $this->state->setMultiple([
        'notify_num_sent' => $num_sent,
        'notify_num_failed' => $num_fail,
      ]);
      // @todo shouldn't this reset happen after every sending is done?
      $this->notify->setSkippedNodes([]);
      $this->notify->setSkippedComments([]);
    }
    // Truncate.
    elseif (1 == $values['process']) {
      [$res_nodes, $res_comms, $res_nopub, $res_copub, $res_nounp, $res_counp] = $this->notify->selectContent();
      foreach ($res_nopub as $row) {
        $q = $db_connection->delete('notify_unpublished_queue', 'n');
        $q->condition('n.cid', 0);
        $q->condition('n.nid', $row->nid);
        $q->execute();
      }
      foreach ($res_copub as $row) {
        $q = $db_connection->delete('notify_unpublished_queue', 'n');
        $q->condition('n.cid', $row->cid);
        $q->condition('n.nid', $row->nid);
        $q->execute();
      }

      // @todo move this logic to service class?
      $this->state->setMultiple([
        'notify_send_start' => \Drupal::time()->getRequestTime(),
        'notify_send_last' => \Drupal::time()->getRequestTime(),
        'notify_cron_next' => 0,
        'notify_users' => [],
      ]);
      $this->notify->setSkippedNodes([]);
      $this->notify->setSkippedComments([]);
      $this->messenger->addMessage($this->t('The notification queue has been truncated. No e-mail were sent.'));
      if ($watchdog_level <= 1) {
        \Drupal::logger('notify')->notice('Notification queue truncated.');
      }
      return;
    }
    // Override.
    elseif (2 == $values['process']) {
      $last_date = strtotime($values['lastdate']);
      $this->state->setMultiple([
        'notify_send_last' => $last_date,
      ]);
      $this->messenger->addMessage($this->t('Timestamp overridden'));
    }
  }

}
