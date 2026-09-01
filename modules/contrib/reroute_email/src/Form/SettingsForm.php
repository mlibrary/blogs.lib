<?php

namespace Drupal\reroute_email\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Url;
use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Implements a settings form for Reroute Email configuration.
 */
class SettingsForm extends ConfigFormBase implements TrustedCallbackInterface {

  /**
   * An editable config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $rerouteConfig;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reroute_email_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['reroute_email.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['textareaRowsValue'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('messenger'),
      $container->get('config.typed'),
    );
  }

  /**
   * Constructs a new object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
    MessengerInterface $messenger,
    TypedConfigManagerInterface $typed_config_manager,
  ) {
    parent::__construct(
      $config_factory,
      $typed_config_manager,
    );
    $this->rerouteConfig = $this->config('reroute_email.settings');
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if ($this->moduleHandler->moduleExists('symfony_mailer')) {
      if ($this->moduleHandler->moduleExists('reroute_email_symfony_mailer')) {
        // Provide instruction for a user
        // how to configure rerouting for Symfony Mailer.
        $this->messenger->addMessage(
          $this->t('Drupal Symfony Mailer module is enabled. Those steps must be ensured to reroute emails properly:<br />
            1. Email rerouting should be enabled in the Reroute Email module settings (this page).<br />
            2. Reroute Email Adjuster should be added in your <a href=":mailer_policy_link">Mailer policy(ies)</a> settings of the Symfony Mailer module.', [
              ':mailer_policy_link' => Url::fromRoute('entity.mailer_policy.collection')->toString(),
            ]
          )
        );
      }
      else {
        $this->messenger->addWarning($this->t('<a href=":url">Drupal Symfony Mailer</a> module is enabled. "Reroute Email (Symfony Mailer support)" module must be enabled for the proper rerouting of emails.', [
          ':url' => Url::fromRoute('entity.mailer_policy.collection')->toString(),
        ]));
      }
    }

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable rerouting'),
      '#default_value' => $this->rerouteConfig->get(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE),
      '#description' => $this->t('Check this box if you want to enable email rerouting. Uncheck to disable rerouting.'),
      '#config' => [
        'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE,
      ],
    ];

    $states = [
      'visible' => [':input[name=' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE . ']' => ['checked' => TRUE]],
    ];

    $form['container'] = [
      '#type' => 'container',
      '#states' => $states,
    ];

    static::buildSettingsForm($form['container'], $this->rerouteConfig->getRawData());
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS,
    ];
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST,
    ];
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES,
    ];
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION,
    ];
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE,
    ];
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS,
    ];
    $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP]['#config'] = [
      'key' => 'reroute_email.settings:' . RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * The static list with default rows.
   *
   * @param array $form
   *   The form array.
   * @param array|null $params
   *   The params array.
   */
  public static function buildSettingsForm(array &$form, ?array $params): void {
    $siteConfig = \Drupal::config('system.site');

    $default_address = $params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS] ?? NULL;
    if (NULL === $default_address) {
      $default_address = $siteConfig->get('mail');
    }

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => t('Rerouting email addresses'),
      '#default_value' => $default_address,
      '#description' => t('Provide a comma-delimited list of email addresses. Every destination email address which is not fit with "Skip email rerouting for" lists will be rerouted to these addresses.<br/>If this field is empty and no value is provided, all outgoing emails would be aborted and the email would be recorded in the recent log entries (if enabled).'),
      '#element_validate' => [
        [static::class, 'validateMultipleEmails'],
        [static::class, 'validateMultipleUnique'],
      ],
      '#reroute_config_delimiter' => ', ',
      '#pre_render' => [[static::class, 'textareaRowsValue']],
    ];

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => t('Skip email rerouting for email addresses:'),
      '#default_value' => $params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST] ?? '',
      '#description' => t('Provide a line-delimited list of email addresses to pass through. All emails to addresses from this list will not be rerouted.<br/>A patterns like "*@example.com" and "name+*@example.com" can be used to add all emails by its domain or the pattern.'),
      '#element_validate' => [
        [static::class, 'validateMultipleEmails'],
        [static::class, 'validateMultipleUnique'],
      ],
      '#pre_render' => [[static::class, 'textareaRowsValue']],
    ];

    $roles_storage = \Drupal::service('entity_type.manager')->getStorage('user_role');
    $roles = [];
    foreach ($roles_storage->loadMultiple() as $role) {
      /** @var \Drupal\user\RoleInterface $role */
      if ($role->id() !== 'anonymous') {
        $roles[$role->id()] = $role->get('label');
      }
    }
    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES] = [
      '#type' => 'checkboxes',
      '#title' => t('Skip email rerouting for roles:'),
      '#description' => t("Emails that belong to users with selected roles won't be rerouted."),
      '#options' => $roles,
      '#default_value' => (array) ($params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES] ?? []),
    ];

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION] = [
      '#type' => 'checkbox',
      '#title' => t('Show rerouting description in mail body'),
      '#default_value' => $params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION] ?? FALSE,
      '#description' => t('Check this box if you want a message to be inserted into the email body when the mail is being rerouted. Otherwise, SMTP headers will be used to describe the rerouting. If sending rich-text email, leave this unchecked so that the body of the email will not be disturbed.'),
    ];

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE] = [
      '#type' => 'checkbox',
      '#title' => t('Display a Drupal status message after rerouting'),
      '#default_value' => $params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE] ?? FALSE,
      '#description' => t('Check this box if you would like a Drupal status message to be displayed to users after submitting an email to let them know it was aborted to send or rerouted to a different email address.'),
    ];

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER] = [
      '#type' => 'details',
      '#title' => t('Mail keys settings'),
      '#open' => (!empty($params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS]) || !empty($params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP])),
    ];

    // Format a list of modules that implement hook_mail.
    $mail_modules = [];
    \Drupal::service('module_handler')->invokeAllWith('mail', function (callable $hook, string $module) use (&$mail_modules) {
      $mail_modules[] = t("%module's module possible mail keys are `@machine_name`, `@machine_name_%`;", [
        '%module' => \Drupal::service('extension.list.module')->getName($module) ?? $module,
        '@machine_name' => $module,
      ]);
    });
    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER]['modules'] = [
      [
        '#type' => 'item',
        '#plain_text' => t('Here is a list of modules that send emails (`%` is one of a specific mail key provided by the module):'),
      ],
      [
        '#theme' => 'item_list',
        '#items' => $mail_modules,
      ],
      [
        '#type' => 'item',
        '#description' => t('Provide a line-delimited list of message keys to be rerouted in the text areas below.<br/>Either module machine name or specific mail key can be used for that. If left empty (as it is by default), all emails will be selected for rerouting.'),
      ],
    ];

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS] = [
      '#title' => t('Filter emails FOR rerouting by their mail keys:'),
      '#type' => 'textarea',
      '#rows' => 2,
      '#element_validate' => [[static::class, 'validateMultipleUnique']],
      '#pre_render' => [[static::class, 'textareaRowsValue']],
      '#default_value' => $params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS] ?? '',
      '#description' => t('Use case: we need to reroute only a few specific mail keys (specified mail keys will be rerouted, all other emails will NOT be rerouted).'),
    ];

    $form[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP] = [
      '#title' => t('Filter emails FROM rerouting by their mail keys:'),
      '#type' => 'textarea',
      '#rows' => 2,
      '#element_validate' => [[static::class, 'validateMultipleUnique']],
      '#pre_render' => [[static::class, 'textareaRowsValue']],
      '#default_value' => $params[RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP] ?? '',
      '#description' => t('Use case: we need to reroute all outgoing emails except a few mail keys (specified mail keys will NOT be rerouted, all other emails will be rerouted).'),
    ];
  }

  /**
   * Adjust rows value according to the content size.
   *
   * @param array $element
   *   The render array to add the access denied message to.
   *
   * @return array
   *   The updated render array.
   */
  public static function textareaRowsValue(array $element): array {
    $size = mb_substr_count($element['#default_value'] ?? '', PHP_EOL) + 1;
    if ($size > $element['#rows']) {
      $element['#rows'] = min($size, 10);
    }
    return $element;
  }

  /**
   * Validate multiple email addresses field.
   *
   * @param array $element
   *   A field array to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateMultipleEmails(array $element, FormStateInterface $form_state): void {
    // Allow only valid email addresses.
    $addresses = reroute_email_split_string($form_state->getValue($element['#name']));
    foreach ($addresses as $address) {
      if (!\Drupal::service('email.validator')->isValid($address)) {
        $form_state->setErrorByName($element['#name'], t('@address is not a valid email address.', ['@address' => $address]));
      }
    }
  }

  /**
   * Validate multiple email addresses field.
   *
   * @param array $element
   *   A field array to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateMultipleUnique(array $element, FormStateInterface $form_state): void {
    // String "email@example.com; ;; , ,," save just as "email@example.com".
    // This will be ignored if any validation errors occur.
    $form_state->setValue($element['#name'], implode($element['#reroute_config_delimiter'] ?? PHP_EOL, reroute_email_split_string($form_state->getValue($element['#name']))));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->rerouteConfig
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ENABLE))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES, array_values(array_filter($form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES))))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS))
      ->set(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP, $form_state->getValue(RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
