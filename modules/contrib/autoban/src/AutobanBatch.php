<?php

namespace Drupal\autoban;

/**
 * @file
 * Contains \Drupal\autoban\AutobanBatch.
 */

/**
 * Class AutobanBatch.
 *
 * Provides the batch operations.
 *
 * @ingroup autoban
 */
class AutobanBatch {

  /**
   * IP ban.
   */
  public static function ipBan($rule, &$context) {
    $controller = \Drupal::service('autoban');
    $banned_ip = $controller->getBannedIp($rule);
    $banned = $controller->banIpList($banned_ip, $rule);

    $context['message'] = t('rule %rule banned @banned', [
      '%rule' => $rule,
      '@banned' => $banned,
    ]);

    // Calculate total banned IPs for all autoban rules.
    $total_banned = $banned;
    if (isset($context['results'])) {
      $total_banned += intval($context['results']);
    }
    $context['results'] = $total_banned;
  }

  /**
   * IP ban finished.
   */
  public static function ipBanFinished($success, $results, $operations) {
    if ($success) {
      $message = t('IP bans was finished. Total banned: %results', ['%results' => $results]);
      \Drupal::messenger()->addMessage($message);
      \Drupal::service('logger.factory')->get('autoban')->notice($message);
    }
  }

}
