<?php

/**
 * @file
 * Autoban deploy.
 */

/**
 * Rename force_mode to autoban_force_mode setting field.
 */
function autoban_deploy_10001() {
  $config = \Drupal::configFactory()->getEditable('autoban.settings');

  if ($config->get('force_mode') !== NULL) {
    $config
      ->set('autoban_force_mode', $config->get('force_mode'))
      ->clear('force_mode')
      ->save();
  }
}
