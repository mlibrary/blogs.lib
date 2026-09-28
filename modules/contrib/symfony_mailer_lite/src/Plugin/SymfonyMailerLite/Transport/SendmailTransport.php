<?php

namespace Drupal\symfony_mailer_lite\Plugin\SymfonyMailerLite\Transport;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;

/**
 * Defines the native Mail Transport plug-in.
 *
 * @SymfonyMailerLiteTransport(
 *   id = "sendmail",
 *   label = @Translation("Sendmail"),
 *   description = @Translation("Use the local sendmail binary to send emails."),
 * )
 */
class SendmailTransport extends TransportBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'query' => ['command' => '']
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $commands = Settings::get('mailer_sendmail_commands', []);
    $commands = ['_default_' => $this->t('&lt;Default&gt;')] + array_combine($commands, $commands);

    $form['command'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sendmail Command'),
      '#options' => $commands,
      '#default_value' => !empty($this->configuration['query']['command']) ? $this->configuration['query']['command'] : '_default_',
      '#required' => TRUE,
      '#description' => $this->t('Configure additional sendmail commands by adding or updating <a href="https://git.drupalcode.org/project/symfony_mailer_lite/-/tree/2.0.x/README.md#using-custom-sendmail-commands">$settings[\'mailer_sendmail_commands\']</a> in your settings.php or settings.local.php file.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $command = $form_state->getValue('command');
    if ($command === '_default_') {
      $command = '';
    }
    $this->configuration['query']['command'] = $command;
  }



}
