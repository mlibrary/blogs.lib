<?php

namespace Drupal\glightbox;

/**
 * An interface for checking if glightbox should be active.
 */
interface ActivationCheckInterface {

  /**
   * Check if glightbox should be activated for the current page.
   *
   * @return bool
   *   If glightbox should be active.
   */
  public function isActive();

}
