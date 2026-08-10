<?php

namespace Drupal\autoban;

/**
 * @file
 * Contains \Drupal\autoban\Utils.
 */

/**
 * Class AutobanUtils.
 *
 * Provides utils.
 *
 * @ingroup autoban
 */
class AutobanUtils {

  const AUTOBAN_USER_ANY = 0;
  const AUTOBAN_USER_ANONYMOUS = 1;
  const AUTOBAN_USER_AUTHENTICATED = 2;
  const AUTOBAN_USER_ANONYMOUS_STRICT = 3;
  const AUTOBAN_USER_AUTHENTICATED_STRICT = 4;
  const AUTOBAN_FROM_ANALYZE = '*from_analyze*';
  const AUTOBAN_RULE_ANY = 0;
  const AUTOBAN_RULE_MANUAL = 1;
  const AUTOBAN_RULE_AUTO = 2;

  /**
   * Is from Analyze page.
   *
   * @param string $string
   *   Autoban string.
   *
   * @return bool
   *   Is from analyze?
   */
  public static function isFromAnalyze($string) {
    return trim($string) === self::AUTOBAN_FROM_ANALYZE;
  }

}
