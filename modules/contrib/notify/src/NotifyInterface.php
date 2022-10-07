<?php

namespace Drupal\notify;

use Drupal\comment\CommentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for Notify service.
 */
interface NotifyInterface {

  /**
   * Flag for never sending notifications.
   *
   * @var int
   */
  const PERIOD_NEVER = -1;

  /**
   * Flag for continuously sending notifications.
   *
   * @var int
   */
  const PERIOD_ALWAYS = 0;

  /**
   * Prefix.
   *
   * @var string
   */
  const NODE_TYPE = 'notify_node_type_';

  /**
   * Sets notification settings for a user.
   *
   * @param int $uid
   *   The ID of the user for which to set notification settings.
   * @param array $values
   *   The settings to set.
   */
  public function setUserNotify(int $uid, array $values): void;

  /**
   * Removes a single node from the notify queue.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to skip.
   *
   * @return $this
   */
  public function skipNode(NodeInterface $node): NotifyInterface;

  /**
   * Returns a list of node ID's that get skipped from notifications.
   *
   * @return int[]
   *   A list of node ID's.
   */
  public function getSkippedNodes(): array;

  /**
   * Sets a list of node ID's to get skipped from notifications.
   *
   * @param int[] $nids
   *   The list of node ID's to skip.
   *
   * @return $this
   */
  public function setSkippedNodes(array $nids): NotifyInterface;

  /**
   * Removes a single comment from the notify queue.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment to skip.
   *
   * @return $this
   */
  public function skipComment(CommentInterface $comment): NotifyInterface;

  /**
   * Returns a list of comment ID's that get skipped from notifications.
   *
   * @return int[]
   *   A list of comment ID's.
   */
  public function getSkippedComments(): array;

  /**
   * Sets a list of comment ID's to get skipped from notifications.
   *
   * @param int[] $cids
   *   The list of comment ID's to skip.
   *
   * @return $this
   */
  public function setSkippedComments(array $cids): NotifyInterface;

  /**
   * Subscribes all non-blocked users for notifications.
   *
   * For each user not already subscribed to notifications, the default
   * notification settings will be applied to each of them.
   */
  public function bulkSubscribeUsers(): void;

  /**
   * Returns a list of tracked types.
   *
   * @param bool $full_list_when_empty
   *   If true, all node types are returned if none are configured.
   *
   * @return string[]
   *   A list of tracked content type names.
   */
  public function getContentTypes(bool $full_list_when_empty): array;

  /**
   * Counts the various types of content.
   *
   * @return array
   *   A list of counted content:
   *   - np;
   *   - cp;
   *   - nn;
   *   - cn;
   *   - bu;
   *   - cu.
   */
  public function countContent(): array;

  /**
   * Helper function to set up query objects to select content for
   * counting and sending.
   *
   * Return array has six values:
   * - ordinary published entities: nodes, comments;
   * - in unpublished queue:
   *   published nodes, published comments,
   *   unpublished nodes, unpublished comments,
   *
   * @return array
   *   res_nodes, res_comms, res_nopub, res_copub, res_nounp, res_counp.
   */
  public function selectContent(): array;

  /**
   * Computes the next time a notification should be sent.
   *
   * @param int $send_last
   *   Timestamp of last notification.
   *
   * @return int
   *   -1 never, 0 send instantly, else next time to notify.
   */
  public function nextNotification(int $send_last): int;

  /**
   * Computes the next as the sending hour today.
   *
   * @return int
   *   @todo describe.
   */
  public function cronNext(int $next_time_to_send): int;

  /**
   * Sends a batch of e-mail notifications.
   *
   * @return array
   *   - Number of sent mails;
   *   - Number of fails.
   */
  public function send(): array;

}
