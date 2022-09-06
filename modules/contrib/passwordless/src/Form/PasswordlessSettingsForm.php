<?php

namespace Drupal\passwordless\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure passwordless login settings for this site.
 */
class PasswordlessSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'passwordless_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('passwordless.settings');

    $default_format = filter_default_format();

    $form['passwordless_settings'] = ['#tree' => TRUE];

    $form['passwordless_settings']['passwordless_add_css'] = [
      '#type' => 'checkbox',
      '#title' => t('Include stylesheet on login page'),
      '#description' => t('If enabled, a stylesheet will be included only on the login page, to style #passwordless-help-link. Clearing the cache might be necessary for any changes to take effect.'),
      '#default_value' => $config->get('passwordless_add_css'),
    ];

    $form['passwordless_settings']['passwordless_show_help'] = [
      '#type' => 'checkbox',
      '#title' => t('Show Passwordless help'),
      '#description' => t('Enable help page and provide link to it from the login form.'),
      '#default_value' => $config->get('passwordless_show_help'),
    ];

    $form['passwordless_settings']['passwordless_help_link_text'] = [
      '#type' => 'textfield',
      '#title' => t('Passwordless help-link text'),
      '#description' => t('Text to display in the login form.'),
      '#default_value' => $config->get('passwordless_help_link_text'),
    ];

    $form['passwordless_settings']['passwordless_help_text'] = [
      '#type' => 'text_format',
      '#title' => t('Passwordless help-page text'),
      '#description' => t('Text to display in the help page.'),
      '#default_value' => $config->get('passwordless_help_text')['value'],
      '#format' => empty($config->get('passwordless_help_text')['format']) ? $default_format : $config->get('passwordless_help_text')['format'],
    ];

    $form['passwordless_settings']['passwordless_toggle_sent_page'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable confirmation page'),
      '#description' => t('Check this box to display the confirmation message on a different page. Uncheck it to display it as a regular Drupal message. @test', ['@test' => Link::fromTextAndUrl(t('You can also test the confirmation page.'), new Url('passwordless.user_login_sent', [], ['attributes' => ['target' => '_blank']]))]),
      '#default_value' => $config->get('passwordless_toggle_sent_page'),
    ];

    $form['passwordless_settings']['passwordless_sent_title_text'] = [
      '#type' => 'textfield',
      '#title' => t('Confirmation-page title'),
      '#description' => t('Text to display as the title for the confirmation page.'),
      '#default_value' => $config->get('passwordless_sent_title_text'),
    ];

    $form['passwordless_settings']['passwordless_sent_page_text'] = [
      '#type' => 'text_format',
      '#title' => t('Confirmation-page text'),
      '#description' => t('Text to display as the body for the confirmation page.'),
      '#default_value' => $config->get('passwordless_sent_page_text')['value'],
      '#format' => empty($config->get('passwordless_sent_page_text')['format']) ? $default_format : $config->get('passwordless_sent_page_text')['format'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('passwordless.settings');
    foreach ($form_state->getValue('passwordless_settings') as $key => $value) {
      $config->set($key, $value);
    }

    $config->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'passwordless.settings',
    ];
  }
}
