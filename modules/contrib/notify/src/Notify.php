<?php

namespace Drupal\notify;

use Drupal\comment\CommentInterface;
use Drupal\comment\Entity\Comment;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Notify service class.
 */
class Notify implements NotifyInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The notify settings.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The datetime service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs a new Notify object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The datetime service.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, StateInterface $state, TimeInterface $time) {
    $this->database = $database;
    $this->config = $config_factory->get('notify.settings');
    $this->state = $state;
    $this->setDatetime($time);
  }

  /**
   * Sets the datetime service.
   *
   * Mostly useful for use in automated tests.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The datetime service.
   */
  public function setDatetime(TimeInterface $time) {
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public function setUserNotify(int $uid, array $values): void {
    // Default values.
    $values += [
      'status' => 1,
      'node' => 0,
      'comment' => 0,
    ];

    // Check if a record already exists for user.
    $exists = $this->database->select('notify')
      ->condition('uid', $uid)
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($exists) {
      // Update the record.
      $this->database->update('notify')
        ->fields([
          'status' => $values['status'],
          'node' => $values['node'],
          'comment' => $values['comment'],
        ])
        ->condition('uid', $uid)
        ->execute();
    }
    else {
      // Insert new record.
      $this->database->insert('notify')
        ->fields([
          'uid' => $uid,
          'status' => $values['status'],
          'node' => $values['node'],
          'comment' => $values['comment'],
        ])
        ->execute();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function bulkSubscribeUsers(): void {
    // Get uids of non-subscribers from {notify}.
    $uids = $this->database->select('notify', 'n')
      ->fields('n', ['uid'])
      ->condition('n.status', 0, '=')
      ->execute()
      ->fetchAllKeyed(0, 0);

    if (!empty($uids)) {
      // Subscribe them all using the default settings.
      $this->database->update('notify')
        ->fields([
          'status' => 1,
          'node' => $this->config->get('notify_def_node'),
          'comment' => $this->config->get('notify_def_comment'),
          'attempts' => 0,
        ])
        ->condition('uid', $uids, 'IN')
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function skipNode(NodeInterface $node): NotifyInterface {
    $skipped = $this->getSkippedNodes();
    $skipped[$node->id()] = $node->id();
    $this->setSkippedNodes($skipped);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkippedNodes(): array {
    return $this->state->get('notify_skip_nodes', []);
  }

  /**
   * {@inheritdoc}
   */
  public function setSkippedNodes(array $nids): NotifyInterface {
    $this->state->set('notify_skip_nodes', $nids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function skipComment(CommentInterface $comment): NotifyInterface {
    $skipped = $this->getSkippedComments();
    $skipped[$comment->id()] = $comment->id();
    $this->setSkippedComments($skipped);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkippedComments(): array {
    return $this->state->get('notify_skip_comments', []);
  }

  /**
   * {@inheritdoc}
   */
  public function setSkippedComments(array $cids): NotifyInterface {
    $this->state->set('notify_skip_comments', $cids);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentTypes(bool $full_list_when_empty): array {
    $node_types = [];
    foreach (NodeType::loadMultiple() as $type => $object) {
      if ($this->config->get(static::NODE_TYPE . $type)) {
        $node_types[] = $type;
      }
    }
    if ($full_list_when_empty && empty($node_types)) {
      foreach (NodeType::loadMultiple() as $type => $name) {
        $node_types[] = $type;
      }
    }

    return $node_types;
  }

  /**
   * {@inheritdoc}
   */
  public function countContent(): array {
    list($nids, $cids, $res_nopub, $res_copub, $res_nounp, $res_counp) = $this->selectContent();

    $np = ($nids) ? count($nids) : 0;
    $cp = ($cids) ? count($cids) : 0;
    $nn = ($res_nopub) ? count($res_nopub) : 0;
    $cn = ($res_copub) ? count($res_copub) : 0;
    $bu = ($res_nounp) ? count($res_nounp) : 0;
    $cu = ($res_counp) ? count($res_counp) : 0;

    return [$np, $cp, $nn, $cn, $bu, $cu];
  }

  /**
   * {@inheritdoc}
   */
  public function selectContent(): array {
    $users = $this->state->get('notify_users');
    $batch_remain = $users ? count($users) : 0;
    $since = $this->state->get('notify_send_last', 0);
    if ($batch_remain) {
      $send_start = $this->state->get('notify_send_start', 0);
    }
    else {
      $send_start = $this->time->getRequestTime();
    }
    if (!$since) {
      $period = $this->config->get('notify_period');
      if ($period > 0) {
        $since = $send_start - $period;
      }
    }

    $all = NodeType::loadMultiple();
    $ntype = [];
    foreach ($all as $type => $object) {
      $ntype[] = $type;
    }

    // Build query object to fetch new nodes.
    $q = $this->database->select('node', 'n');
    $q->join('node_field_data', 'u', 'n.nid = u.nid');
    $q->fields('n', ['nid']);
    if (count($ntype) >= 1) {
      $q->condition('n.type', $ntype, 'IN');
$q->condition('u.status', 1);
    }
    if ($this->config->get('notify_include_updates')) {
      $q->condition((new Condition('OR'))
        ->condition((new Condition('AND'))
          ->condition('u.created', $since, '>')
          ->condition('u.created', $send_start, '<='))
        ->condition((new Condition('AND'))
          ->condition('u.changed', $since, '>')
          ->condition('u.changed', $send_start, '<=')
        ));
      $q->orderBy('u.created', 'asc');
      $q->allowRowCount = TRUE;
      $res_nodes = $q->execute()->fetchAllAssoc('nid');
    }
    else {
      $q->condition('u.created', $since, '>');
      $q->condition('u.created', $send_start, '<=');
      $q->orderBy('u.created', 'asc');
      $q->allowRowCount = TRUE;
      $res_nodes = $q->execute()->fetchAllAssoc('nid');
    }

    // Get published nodes in unpublished queue.
    $q = $this->database->select('notify_unpublished_queue', 'q');
    $q->join('node', 'n', 'q.nid = n.nid');
    $q->join('node_field_data', 'u', 'q.nid = u.nid');
    $q->fields('q', ['nid', 'cid']);
    $q->condition('u.status', 1, '=');
    $q->condition('q.cid', 0, '=');
    $q->orderBy('q.nid', 'asc');
    $q->allowRowCount = TRUE;
    $res_nopub = $q->execute()->fetchAllAssoc('nid');

    // Get unpublished nodes in unpublished queue.
    $q = $this->database->select('notify_unpublished_queue', 'q');
    $q->join('node', 'n', 'q.nid = n.nid');
    $q->join('node_field_data', 'u', 'q.nid = u.nid');
    $q->fields('q', ['nid', 'cid']);
    $q->condition('u.status', 0, '=');
    $q->condition('q.cid', 0, '=');
    $q->orderBy('q.nid', 'asc');
    $q->allowRowCount = TRUE;
    $res_nounp = $q->execute()->fetchAllAssoc('nid');

    if (\Drupal::service('module_handler')->moduleExists('comment')) {
      // Fetch new published comments.
      $q = $this->database->select('comment', 'c');
      $q->join('comment_field_data', 'v', 'c.cid = v.cid');
      $q->join('node', 'n', 'v.entity_id = n.nid');
      $q->fields('c', ['cid']);
      if (count($ntype) >= 1) {
        $q->condition('n.type', $ntype, 'IN');
      }
      // ->condition(((new Condition('AND'))->condition(true)
      // (AND () (OR (AND ()())  (AND ()())))
      if ($this->config->get('notify_include_updates')) {
        // dpm('incl. updates', 'comms');
        // Only comments attached to nodes are called 'comment'.
        $q->condition((new Condition('AND'))
          ->condition('c.comment_type', 'comment', '=')
          ->condition((new Condition('OR'))
            ->condition((new Condition('AND'))
              ->condition('v.created', $since, '>')
              ->condition('v.created', $send_start, '<='))
            ->condition((new Condition('AND'))
              ->condition('v.changed', $since, '>')
              ->condition('v.changed', $send_start, '<='))));
        $q->orderBy('v.created', 'asc');
        $q->allowRowCount = TRUE;
        $res_comms = $q->execute()->fetchAllAssoc('cid');
      }
      else {
        // dpm('no updates', 'comms');.
        $q->condition('c.comment_type', 'comment', '=');
        $q->condition('v.created', $since, '>');
        $q->condition('v.created', $send_start, '<=');
        $q->orderBy('v.created', 'asc');
        $q->allowRowCount = TRUE;
        $res_comms = $q->execute()->fetchAllAssoc('cid');
      }
      // Foreach ($res_comms as $row) {
      // dpm($row, 'comms');
      // }
      // Get published comments in unpublished queue.
      $q = $this->database->select('notify_unpublished_queue', 'q');
      $q->join('comment', 'c', 'q.cid = c.cid');
      $q->join('comment_field_data', 'v', 'q.cid = v.cid');
      $q->fields('q', ['cid', 'nid']);
      $q->condition('v.status', 1, '=');
      $q->orderBy('q.cid', 'asc');
      $q->allowRowCount = TRUE;
      $res_copub = $q->execute()->fetchAllAssoc('cid');
      // Foreach ($res_copub as $row) {
      // dpm($row, 'copub');
      // }
      // Get unpublished comments in unpublished queue.
      $q = $this->database->select('notify_unpublished_queue', 'q');
      $q->join('comment', 'c', 'q.cid = c.cid');
      $q->join('comment_field_data', 'v', 'q.cid = v.cid');
      $q->fields('q', ['cid', 'nid']);
      $q->condition('v.status', 0, '=');
      $q->orderBy('q.cid', 'asc');
      $q->allowRowCount = TRUE;
      $res_counp = $q->execute()->fetchAllAssoc('cid');
      // Foreach ($res_counp as $row) {
      // dpm($row, 'counp');
      // }.
    }
    else {
      $res_comms = $res_copub = $res_counp = NULL;
    }

    return [
      $res_nodes,
      $res_comms,
      $res_nopub,
      $res_copub,
      $res_nounp,
      $res_counp,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function nextNotification(int $send_last): int {
    $period = $this->config->get('notify_period');
    // Two special cases: Never and instantly.
    if ($period < 0) {
      return static::PERIOD_NEVER;
    }
    elseif (!$period) {
      return static::PERIOD_ALWAYS;
    }
    $next_time_to_send = $send_last + $period;
    if ($period < 86400) {
      if ($this->time->getRequestTime() >= $next_time_to_send) {
        return static::PERIOD_ALWAYS;
      }
      else {
        return $next_time_to_send;
      }
    }

    // Interval >= 1 day.
    $cron_next = $this->state->get('notify_cron_next', 0);
    if (!$cron_next) {
      $cron_next = $this->cronNext($next_time_to_send);
      $this->state->set('notify_cron_next', $cron_next);
    }

    return($cron_next);
  }

  /**
   * {@inheritdoc}
   */
  public function cronNext(int $next_time_to_send): int {
    $send_hour = $this->config->get('notify_send_hour');
    // Compute the next as the sending hour today.
    $cron_next = strtotime(date('Y-m-d ', $next_time_to_send) . $send_hour . ':00:00');

    return $cron_next;
  }

  /**
   * {@inheritdoc}
   */
  public function send(): array {
    $user = \Drupal::currentUser();
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    $host = $request->getSchemeAndHttpHost();

    $send_start = $this->state->get('notify_send_start', 0);
    $num_sent = 0;
    $num_fail = 0;
    [$res_nodes, $res_comms, $res_nopub, $res_copub, $res_nounp, $res_counp] = $this->selectContent();

    $defaultlist = $this->getContentTypes(TRUE);

    // Get the nodes and comments queued.
    $count = 0;
    $nodes = $comments = [];
    // Ordinary nodes.
    foreach ($res_nodes as $row) {
      $nodes[$row->nid] = Node::load($row->nid);
      $count++;
    }
    // Ordinary comments.
    if ($res_comms) {
      foreach ($res_nopub as $row) {
        if (!isset($nodes[$row->nid])) {
          $nodes[$row->nid] = Node::load($row->nid);
          $count++;
        }
      }
      foreach ($res_comms as $row) {
        $comment = Comment::load($row->cid);
        $comments[$comment->get('entity_id')->target_id][$row->cid] = $comment;
        $count++;
      }
      foreach ($res_copub as $row) {
        if (!isset($comments[$row->nid][$row->cid])) {
          $comments[$row->get('entity_id')->target_id][$row->cid] = Comment::load($row->cid);
          $count++;
        }
      }
    }
    // Published nodes in unpublished queue.
    foreach ($res_nopub as $row) {
      if (!isset($nodes[$row->nid])) {
        $nodes[$row->nid] = Node::load($row->nid);
        $count++;
      }
    }
    // Unpublished nodes in unpublished queue.
    foreach ($res_nounp as $row) {
      if (!isset($nodes[$row->nid])) {
        $nodes[$row->nid] = Node::load($row->nid);
        $count++;
      }
    }

    if ($count) {
      $uresult = $this->state->get('notify_users');
      if (empty($uresult)) {
        // Set up for sending a new batch. Init all variables.
        $result = $this->database->select('notify', 'n');
        $result->join('users', 'u', 'n.uid = u.uid');
        $result->join('users_field_data', 'v', 'n.uid = v.uid');
        $result->fields('u', ['uid']);
        $result->fields('v', ['name', 'mail']);
        $result->fields('n', ['node', 'comment', 'status']);
        $result->condition('v.status', 1);
        $result->condition('n.status', 1);
        $result->condition('n.attempts', 5, '<=');
        $result->allowRowCount = TRUE;
        $uresult = $result->execute()->fetchAll(\PDO::FETCH_ASSOC);

        $this->state->setMultiple([
          'notify_send_start' => $this->time->getRequestTime(),
          'notify_users' => $uresult,
          'notify_num_sent' => 0,
          'notify_num_failed' => 0,
        ]);
      }

      $batchlimit = $this->config->get('notify_batch');
      $batchcount = 0;

      // Allow to safely impersonate the recipient so that the node is rendered
      // with correct field permissions.
      $original_user = $user;
      $notify_skip_nodes = $this->getSkippedNodes();
      $notify_skip_comments = $this->getSkippedComments();

      foreach ($uresult as $index => $userrow) {
        if (++$batchcount > $batchlimit) {
          break;
        }
        $userobj = User::load($userrow['uid']);

        // Intentionally replacing the Global $user.
        $user = $userobj;
        $upl = $userobj->getPreferredLangcode();

        $node_body = $comment_body = '';

        // Consider new node content if user has permissions and nodes are ready.
        // $userrow['node']: user subscribes to nodes.
        if ($userrow['node'] && $userobj->hasPermission('access content') && count($nodes)) {

          $node_count = 0;
          // Look at the node.
          foreach ($nodes as $node) {
            // Skip to next if skip flag set for node.
            if (in_array($node->id(), $notify_skip_nodes)) {
              continue;
            }
            // Skip to next if user is not allowed to view this node.
            if (!$userobj->hasPermission('administer nodes') && 0 == $node->isPublished()) {
              continue;
            }
	    // Skip if node type if user is not admin and node is not on defaultlist.
            if (!$userobj->hasPermission('administer nodes') && !in_array($node->getType(), $defaultlist)) {
              continue;
            }
	    // Skip if ???
            if (!$this->config->get('notify_unpublished') && 0 == $node->isPublished()) {
              continue;
            }
	    // Skip if ???
            if (!$node->access('view', $userobj)) {
              continue;
            }

            $field = $this->database->select('notify_subscriptions', 'n')
              ->fields('n', ['uid', 'type'])
              ->condition('uid', $userrow['uid'])
              ->condition('type', $node->getType())
              ->execute()->fetchAssoc();

            if ($field) {
              $ml_level = $this->config->get('notify_multilingual');
              if (!$userobj->hasPermission('administer notify')) {
                if ($ml_level && $node->tnid) {
                  if ($node->language != $upl) {
                    continue;
                  }
                }
                if ((2 == $ml_level) && (0 == $node->tnid) && ('und' != $node->language)) {
                  continue;
                }
                $ignore_unverified = $this->config->get('notify_unverified');
                if ($ignore_unverified && !$node->uid) {
                  continue;
                }
              }

              $node_revs_list = \Drupal::entityTypeManager()->getStorage('node')->revisionIds($node);
              $nrl_vals = array_values($node_revs_list);
              $vers = array_shift($nrl_vals);
              $node_revision = \Drupal::entityTypeManager()
                ->getStorage('node')
                ->loadRevision($vers);

              // Start accumulating the body for the notification
              $node_body .= ++$node_count . '. ' . $node->label();
              if (count($node_revs_list) > 1) {
                $update = '(' . t('last updated by @author', [
                  '@author' => ($node_revision->getOwner()->getDisplayName() ? $node_revision->getOwner()->getDisplayName() : $this->config->get('anonymous')),
                ], ['langcode' => $upl]) . ') ';
              }
              else {
                $update = '';
              }
              if ($userobj->hasPermission('administer nodes')) {
                $status = $node->isPublished() == 1 ? t('[Published]', [], ['langcode' => $upl]) : t('[Unpublished]', [], ['langcode' => $upl]);
              }
              else {
                $status = '';
              }

              $alias = \Drupal::languageManager()->isMultilingual() ? TRUE : FALSE;
              /*
              $options = [
                'alias' => $alias,
                'attributes' => ['class' => ['read-more']],
                'absolute'   => TRUE,
              ];
              */
              // Creates a link do be embedded in HTML mail:
              // $link = Link::fromTextAndUrl(t('Read more'), Url::fromUri('internal:/node/' . $node->id(), $options))->toString();
              // Creates a link to be embedded in plain text mail:
              $link = $host . '/' . Url::fromUri('internal:/node/' . $node->id())->toString();
              $node_body .= ' - see ' . $link . '<br>';
            } // if ($field)
          } // foreach ($nodes as $node)

          // Prepend e-mail header as long as user could access at least one node.
          if ($node_count > 0) {
            $node_body = '<p>' . t('<p>Recent new or updated pages - @count', [
              '@count' => \Drupal::translation()->formatPlural($node_count, '1 new post', '@count new posts', [], ['langcode' => $upl]),
            ], ['langcode' => $upl]) . "<br />" . '</p>' . $node_body;
          }
        }
	//dpm($field, 'field before comments');
	// Need to filter on content type and pernission, see issue #3221814.
        // Consider new comments if user has permissions and comments are ready.
        if ($userrow['comment'] && $userobj->hasPermission('access comments') && count($comments)) {
          $comment_count = 0;
          $node_comment_count = 0;
          $commentlinks = [];
          $commentindex = 0;
          foreach ($comments as $nid => $value) {
            // If we don't already have the node, fetch it.
            if (isset($nodes[$nid])) {
              $node = $nodes[$nid];
            }
            else {
              $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
            }
            if (!$node->access('view', $userobj)) {
              continue;
            }

            // Look at the comment.
            foreach ($value as $commobj) {
	      $cid = $commobj->id();
              $permalink = $commobj->permalink()->toString();
              if (in_array($commobj->id(), $notify_skip_comments)) {
                continue;
              }
              if (!$userobj->hasPermission('administer comments') && 0 == $commobj->isPublished()) {
                continue;
              }
              if (!$userobj->hasPermission('administer comments') && !in_array($node->getType(), $defaultlist)) {
                continue;
              }
              if (!$this->config->get('notify_unpublished') && 0 == $commobj->isPublished()) {
                continue;
              }
              // Determine whether to show comment status.
              if ($userobj->hasPermission('administer comments')) {
                $status = $commobj->isPublished() == 1 ? t('[Published]', [], ['langcode' => $upl]) : t('[Unpublished]', [], ['langcode' => $upl]);
              }
              else {
                $status = '';
              }

              /*
	      $options = [
                'attributes' => ['class' => ['read-more']],
                'fragment'   => 'comment-' . $commobj->id(),
                'absolute'   => TRUE,
              ];
	      */
              // Creates a link do be embedded in HTML mail (needs work):
              //$htmllink = Link::fromTextAndUrl(t('Read more'),   Url::fromUri('internal:/comment/' . $cid, ))))->toString();
	      //dpm($htmllink, 'htmllink');
              // Creates a link to be embedded in plain text mail:
	      $commentlink = $host . $permalink;
	      $commentlinks[$commentindex]['title'] = $node->label();
	      $commentlinks[$commentindex]['link'] = $commentlink;
	      $commentindex++;
              $comment_count++;
            }
          }

          if ($comment_count) {
            $comment_body .= '<br />&nbsp;<br /><p>' . t('Here are links to the @count posted:', [
              '@count' => \Drupal::translation()->formatPlural($comment_count, '1 new comment', '@count new comments'),
            ], ['langcode' => $upl]) . "<br />" . '</p>' . $comment_body;
	    foreach ($commentlinks as $commentlink) {
              $comment_body .= ++$node_comment_count . '. ';
              $comment_body .= t('@title ', [
                '@title' => $commentlink['title'],
              ], ['langcode' => $upl]) . '- see ' . $commentlink['link'] . "<br />";
            }
	  }
        }

        $body = $node_body . $comment_body;
        // If there was anything new, send mail.
        if ($body) {
          $watchdog_level = $this->config->get('notify_watchdog');
          if (\Drupal::service('plugin.manager.mail')->mail('notify', 'notice', $userrow['mail'], $upl,
            ['content' => $body, 'user' => $userobj, 'nodes' => $nodes], NULL, TRUE)) {
            if ($watchdog_level == 0) {
              \Drupal::logger('notify')->notice('User %name (%mail) notified successfully.',
                ['%name' => $userrow['name'], '%mail' => $userrow['mail']]);
            }
            $num_sent++;
          }
          else {
            $num_fail++;
            $q = $this->database->update('notify');
            $q->expression('attempts', 'attempts + 1');
            $q->condition('uid', $userrow['uid']);
            $q->execute();

            if ($watchdog_level <= 2) {
              \Drupal::logger('notify')->notice('User %name (%mail) could not be notified. Mail error.',
                ['%name' => $userrow['name'], '%mail' => $userrow['mail']]);
            }
          }
        }

        unset($uresult[$index]);
        $this->state->set('notify_users', $uresult);
      }
      // Restore the original user session.
      $user = $original_user;
    }
    $users = $this->state->get('notify_users');
    $rest = $users ? count($users) : 0;
    // If $rest is empty, then set notify_send_last.
    if (!$rest) {
      $send_start = $this->state->get('notify_send_start', 0);
      $this->state->set('notify_send_last', $send_start);
      // Force reset.
      $this->state->set('notify_cron_next', 0);

      [$res_nodes, $res_comms, $res_nopub, $res_copub, $res_nounp, $res_counp] = $this->selectContent();
      foreach ($res_nopub as $row) {
        $q = $this->database->delete('notify_unpublished_queue');
        $q->condition('cid', 0);
        $q->condition('nid', $row->nid);
        $q->execute();
      }
      if ($res_copub) {
        foreach ($res_copub as $row) {
          $q = $this->database->delete('notify_unpublished_queue');
          $q->condition('cid', $row->cid);
          $q->condition('nid', $row->nid);
          $q->execute();
        }
      }
    }

    return [$num_sent, $num_fail];
  }

}
