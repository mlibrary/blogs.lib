<?php

namespace Drupal\og_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the configuration form for OG Menu.
 */
class ConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'og_menu_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['og_menu.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('og_menu.settings');

    $form['autocreate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create new menus'),
      '#description' => $this->t('When enabled an OG Menu will automatically be created when new group is created.'),
      '#default_value' => $config->get('autocreate'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('og_menu.settings')
      ->set('autocreate', $form_state->getValue('autocreate'))
      ->save();
  }

}
