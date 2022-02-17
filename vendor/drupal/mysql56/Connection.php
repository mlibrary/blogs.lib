<?php

namespace Drupal\Driver\Database\mysql;

use Drupal\Core\Database\Driver\mysql\Connection as CoreMysqlConnection;

/**
 * MySQL 5.6 implementation of \Drupal\Core\Database\Driver\mysql\Connection.
 */
class Connection extends CoreMysqlConnection {

  /**
   * {@inheritdoc}
   */
  public function __construct(\PDO $connection, array $connection_options) {
    // @see https://www.drupal.org/project/drupal/issues/3218978
    // @todo Remove this when the above issue is committed.
    if ($this->identifierQuotes === ['"', '"'] && strpos($connection_options['init_commands']['sql_mode'], 'ANSI') === FALSE) {
      $this->identifierQuotes = ['`', '`'];
    }
    parent::__construct($connection, $connection_options);
  }

}
