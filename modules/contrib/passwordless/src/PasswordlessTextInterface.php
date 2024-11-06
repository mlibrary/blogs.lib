<?php

namespace Drupal\passwordless;

/**
 * Interface for the passwordless.text service.
 */
interface PasswordlessTextInterface {

  /**
   * Retrieves specific settings from config.
   *
   * @param string $key
   *   The setting key.
   *
   * @return mixed
   *   The sanitized value.
   */
  public function get($key);

}
