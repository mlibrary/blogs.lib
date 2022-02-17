<?php

/**
 * @file
 * Contains \Drupal\og_menu\Form\OgMenuInstanceSettingsForm.
 */

namespace Drupal\og_menu\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OgMenuInstanceSettingsForm.
 *
 * @package Drupal\og_menu\Form
 *
 * @ingroup og_menu
 */
class OgMenuInstanceSettingsForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'OgMenuInstance_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }


  /**
   * Defines the settings form for OG Menu instance entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['OgMenuInstance_settings']['#markup'] = 'Settings form for OG Menu instance entities. Manage field settings here.';
    return $form;
  }

}
