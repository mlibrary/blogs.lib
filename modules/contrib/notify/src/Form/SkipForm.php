<?php

namespace Drupal\notify\Form;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\notify\NotifyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures forms module settings.
 */
class SkipForm extends ConfigFormBase {

  /**
   * The notify service.
   *
   * @var \Drupal\notify\NotifyInterface
   */
  protected $notify;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The messenger service.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The core messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, NotifyInterface $notify, EntityTypeManagerInterface $entity_type_manager, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->notify = $notify;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('notify'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'notify_skip_settings';
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
    $state = \Drupal::state();
    // Fetch list of nodes and comments scheduled for notification.
    [$res_nodes, $res_comms, $res_nopub, $res_copub, $res_nounp, $res_counp] = $this->notify->selectContent();

    // Get nodes.
    $nodes = [];
    $nids = array_keys($res_nodes + $res_nopub + $res_nounp);
    if (!empty($nids)) {
      $nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($nids);
    }

    // Get comments.
    $comments = [];
    $cids = array_keys($res_comms + $res_copub + $res_counp);
    if (!empty($cids)) {
      // Order comments by thread.
      foreach ($this->entityTypeManager->getStorage('comment')->loadMultiple($cids) as $comment) {
        $comments[$comment->get('entity_id')->target_id][$comment->id()] = $comment;
      }
    }

    $form = [];

    $form['#tree'] = TRUE;
    $form['info'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('The following table shows all messages that are candidates for notification emails:'),
      '#suffix' => '</p>',
    ];

    $skipped_nodes = $this->notify->getSkippedNodes();
    $skipped_comments = $this->notify->getSkippedComments();

    $form['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Set skip flags'),
    ];
    $form['settings']['table'] = [
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => [
        $this->t('NID'),
        $this->t('CID'),
        $this->t('Published'),
        $this->t('Created'),
        $this->t('Updated'),
        $this->t('Title'),
        $this->t('Skip'),
      ],
    ];

    $ii = 0;
    foreach ($nodes as $node) {
      $ii++;
      $form['settings']['table'][$ii]['nid'] = [
        '#markup' => $node->id(),
      ];
      $form['settings']['table'][$ii]['cid'] = [
        '#markup' => '-',
      ];
      $form['settings']['table'][$ii]['published'] = [
        '#markup' => $node->isPublished() ? 'Yes' : 'No',
      ];
      $form['settings']['table'][$ii]['created'] = [
        '#markup' => \Drupal::service('date.formatter')->format($node->getCreatedTime(), 'short'),
      ];
      $form['settings']['table'][$ii]['updated'] = [
        '#markup' => ($node->getChangedTime() != $node->getCreatedTime()) ? \Drupal::service('date.formatter')->format($node->getChangedTime(), 'short') : '-',
      ];
      $form['settings']['table'][$ii]['title'] = [
        '#markup' => $node->label(),
      ];
      $flag = in_array($node->id(), $skipped_nodes) ? 1 : 0;
      $form['settings']['table'][$ii]['dist'] = [
        '#type' => 'checkbox',
        '#default_value' => $flag,
      ];
    }
    foreach ($comments as $thread) {
      foreach ($thread as $comment) {
        $ii++;
        $form['settings']['table'][$ii]['nid'] = [
          '#markup' => $comment->get('entity_id')->target_id,
        ];
        $form['settings']['table'][$ii]['cid'] = [
          '#markup' => $comment->id(),
        ];
        $form['settings']['table'][$ii]['published'] = [
          '#markup' => $comment->isPublished() ? 'Yes' : 'No',
        ];
        $form['settings']['table'][$ii]['created'] = [
          '#markup' => \Drupal::service('date.formatter')->format($comment->getCreatedTime(), 'short'),
        ];
        $form['settings']['table'][$ii]['updated'] = [
          '#markup' => ($comment->getChangedTime() != $comment->getCreatedTime()) ? \Drupal::service('date.formatter')->format($comment->getChangedTime(), 'short') : '-',
        ];
        $form['settings']['table'][$ii]['title'] = [
          '#markup' => $comment->label(),
        ];
        $flag = in_array($comment->id(), $skipped_comments) ? 1 : 0;
        $form['settings']['table'][$ii]['dist'] = [
          '#type' => 'checkbox',
          '#default_value' => $flag,
        ];
      }
    }
    $users = $state->get('notify_users');
    $batch_remain = $users ? count($users) : 0;
    if ($batch_remain) {
      $form['info2'] = [
        '#markup' => '<p>' . $this->t('Please note that the list above may be out of sync.  Saving an altered list of skip flags is disabled as long as notifications are being processed.') . '</p> ',
      ];
    }
    else {
      $form['info2'] = [
        '#markup' => '<p>' . $this->t('To flag that <em>no</em> notification about a particular message should be sent, check the checkbox in the “Skip” column. Press “Save settings” to save the flags.') . '</p> ',
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $form_values = $form_state->getCompleteForm();

    $nodes = [];
    $comments = [];
    if (isset($values['settings']['table']) && $values['settings']['table']) {
      foreach ($values['settings']['table'] as $dist => $ii) {
        if ($ii['dist']) {
          $nid = (int) $form_values['settings']['table'][$dist]['nid']['#markup'];
          $cid = (int) $form_values['settings']['table'][$dist]['cid']['#markup'];
          if (empty($cid)) {
            $nodes[$nid] = $nid;
          }
          else {
            $comments[$cid] = $cid;
          }
        }
      }

      $this->notify->setSkippedNodes($nodes);
      $this->notify->setSkippedComments($comments);
    }

    $this->messenger->addMessage($this->t('Skip flags saved.'));
  }

}
