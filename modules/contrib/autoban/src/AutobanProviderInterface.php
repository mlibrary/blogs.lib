<?php

namespace Drupal\autoban;

use Drupal\Core\Database\Connection;

/**
 * Provides an interface defining a AutobanProvider.
 */
interface AutobanProviderInterface {

  /**
   * Get BanProvider id for store in autoban rule.
   *
   * @return string
   *   Ban provider ID.
   */
  public function getId();

  /**
   * Get BanProvider name for choice list.
   *
   * @return string
   *   Human name for user select.
   */
  public function getName();

  /**
   * Get Ban type: single, range and so on.
   *
   * @return string
   *   Human name for ban type.
   */
  public function getBanType();

  /**
   * Has Ban meta data.
   *
   * @return bool
   *   Has the item meta data.
   */
  public function hasMetadata();

  /**
   * Get BanIpManager object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection which will be used get BanIpManager.
   *
   * @return object
   *   BanIpManager object.
   */
  public function getBanIpManager(Connection $connection);

}
