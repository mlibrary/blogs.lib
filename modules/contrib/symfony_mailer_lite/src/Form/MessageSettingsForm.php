<?php

namespace Drupal\symfony_mailer_lite\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\symfony_mailer_lite\Plugin\Mail\SymfonyMailer;

/**
 * Configuration form for Drupal Symfony Mailer Lite message settings.
 */
class MessageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'symfony_mailer_lite_message_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'symfony_mailer_lite.message',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('symfony_mailer_lite.message');

    $form['#tree'] = TRUE;

    $form['description'] = [
      '#markup' => '<p>' . $this->t('This page allows you to configure how e-mail messages are formatted.') . '</p>',
    ];

    $form['formatting'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default Message Format'),
    ];

    $options = [
      SymfonyMailer::FORMAT_HTML => $this->t('HTML'),
      SymfonyMailer::FORMAT_PLAIN => $this->t('Plain Text'),
    ];
    $form['formatting']['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default Content Type'),
      '#options' => $options,
      '#default_value' => $config->get('content_type'),
      '#description' => $this->t('Select the content type for emails that do not specify a content type.'),
    ];

    $form['formatting']['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always use the default content type'),
      '#default_value' => $config->get('override'),
      '#description' => $this->t('If checked, all emails will be sent with the content type selected above. If unchecked, emails will be sent using the content type specified by the module that is sending the email.'),
    ];

    // The filter will operate on plain text so only show formats that escape
    // HTML.
    $formats = [];
    foreach (filter_formats($this->currentUser()) as $format) {
      if ($format->filters('filter_html_escape')->status) {
        $formats[$format->id()] = $format->label();
      }
    }

    $form['formatting']['html_convert_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Text format'),
      '#options' => $formats,
      '#default_value' => $config->get('text_format') ?: filter_fallback_format(),
      '#description' => $this->t('Text format to use when converting a plain text e-mail to HTML. The list of available formats is restricted to those that escape HTML.'),
      '#states' => [
        'visible' => [
          ':input[name="formatting[type]"]' => ['value' => SymfonyMailer::FORMAT_HTML],
        ],
      ],
    ];

    $form['generate_plain'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Plain Text Version'),
      '#description' => $this->t('An alternative plain text version can be generated based on the HTML version if no plain text version
        has been explicitly set. The plain text version will be used by e-mail clients not capable of displaying HTML content.'),
    ];

    $form['generate_plain']['mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate alternative plain text version (recommended).'),
      '#default_value' => $config->get('generate_plain'),
      '#description' => $this->t('Please refer to @link for more details about how the alternative plain text version will be generated.', ['@link' => Link::fromTextAndUrl('html2text', Url::fromUri('http://www.chuggnutt.com/html2text'))->toString()]),
    ];

    $form['character_set'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Character Set'),
      '#description' => '<p>' . $this->t('E-mails need to carry details about the character set which the
        receiving client should use to understand the content of the e-mail.
        The default character set is UTF-8.') . '</p>',
    ];

    $form['character_set']['type'] = [
      '#type' => 'select',
      '#options' => symfony_mailer_lite_get_character_set_options(),
      '#default_value' => $config->get('character_set'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('symfony_mailer_lite.message');
    $config->set('content_type', $form_state->getValue(['formatting', 'type']));
    $config->set('override', $form_state->getValue(['formatting', 'override']));
    $config->set('text_format', $form_state->getValue(['formatting', 'html_convert_format']));
    $config->set('generate_plain', $form_state->getValue(['generate_plain', 'mode']));
    $config->set('character_set', $form_state->getValue(['character_set', 'type']));

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
