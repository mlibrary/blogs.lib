<?php

namespace Drupal\symfony_mailer_lite\Plugin\SymfonyMailerLite\Transport;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the SMTP Mail Transport plug-in.
 *
 * @SymfonyMailerLiteTransport(
 *   id = "smtp",
 *   label = @Translation("SMTP"),
 *   description = @Translation("Use an SMTP server to send emails."),
 * )
 */
class SmtpTransport extends TransportBase implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->moduleHandler = $container->get('module_handler');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->logger = $container->get('logger.factory')->get('symfony_mailer_lite');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user' => '',
      'pass' => '',
      'pass_key' => '',
      'use_key_module' => FALSE,
      'host' => '',
      'port' => '',
      'query' => [
        'verify_peer' => TRUE,
        'local_domain' => '',
        'restart_threshold' => 100,
        'restart_threshold_sleep' => 0,
        'ping_threshold' => 100,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('User name'),
      '#default_value' => $this->configuration['user'],
      '#description' => $this->t('User name to log in.'),
    ];

    // Check if Key module is available
    $key_module_available = $this->moduleHandler->moduleExists('key');

    if ($key_module_available) {
      $form['use_key_module'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Use Key module for secure password storage'),
        '#default_value' => $this->configuration['use_key_module'],
        '#description' => $this->t('Store the password securely using the Key module instead of plain text.'),
      ];
    }
    else {
      $form['key_module_warning'] = [
        '#type' => 'inline_template',
        '#template' => '<div class="messages messages--warning">{{ warning_message }}</div>',
        '#context' => [
          'warning_message' => $this->t('For enhanced security, consider installing the <a target="_blank" href="@url">Key module</a> to store SMTP passwords more securely.', [
            '@url' => 'https://www.drupal.org/project/key'
          ]),
        ],
      ];
      // Hidden field to ensure use_key_module is always FALSE when key module isn't available
      $form['use_key_module'] = [
        '#type' => 'hidden',
        '#value' => FALSE,
      ];
    }

    // By default, keep the existing password except for a new transport
    // (which has empty host).
    $new = empty($this->configuration['host']);
    $form['change_pass'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Change password'),
      '#default_value' => $new,
      '#access' => !$new,
      '#description' => $this->t('Your password is stored; select to change it.'),
      '#states' => [
        'invisible' => [
          ':input[name="use_key_module"]' => ['checked' => TRUE],
        ],
      ],
    ];

    if ($key_module_available) {
      // Key selection for secure storage
      $keys = [];
      $key_storage = $this->entityTypeManager->getStorage('key');
      foreach ($key_storage->loadMultiple() as $key) {
        $keys[$key->id()] = $key->label();
      }

      $form['pass_key'] = [
        '#type' => 'select',
        '#title' => $this->t('Password Key'),
        '#options' => $keys,
        '#empty_option' => $this->t('- Select a key -'),
        '#default_value' => $this->configuration['pass_key'],
        '#description' => $this->t('Select the key that contains the SMTP password. <a href="@url">Manage keys</a>.', [
          '@url' => Url::fromUri('internal:/admin/config/system/keys')->toString(),
        ]),
        '#states' => [
          'visible' => [
            ':input[name="use_key_module"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="use_key_module"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#default_value' => $this->configuration['pass'],
      '#description' => $this->t('Password to log in. <strong>Warning:</strong> This will be stored as plain text in the database.'),
      '#states' => [
        'visible' => [
          [':input[name="use_key_module"]' => ['checked' => FALSE]],
          'AND',
          [':input[name="change_pass"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Host name'),
      '#default_value' => $this->configuration['host'],
      '#description' => $this->t('SMTP host name.'),
      '#required' => TRUE,
    ];

    $form['port'] = [
      '#type' => 'number',
      '#title' => $this->t('Port'),
      '#default_value' => $this->configuration['port'],
      '#description' => $this->t('SMTP port.'),
      '#min' => 0,
      '#max' => 65535,
    ];

    $form['query']['verify_peer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Perform TLS peer verification'),
      '#description' => $this->t('This is recommended for security reasons, however it can be useful to disable it while developing or when using a self-signed certificate.'),
      '#default_value' => $this->configuration['query']['verify_peer'],
    ];

    $form['advanced_options'] = [
      '#type' => 'details',
      '#title' => 'Advanced options',
    ];

    $form['advanced_options']['local_domain'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Local domain'),
      '#default_value' => $this->configuration['query']['local_domain'],
      '#description' => $this->t('The domain name to use in HELO command.'),
    ];

    $form['advanced_options']['restart_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Restart threshold'),
      '#default_value' => $this->configuration['query']['restart_threshold'],
      '#description' => $this->t('The maximum number of messages to send before re-starting the transport.'),
      '#min' => 0,
    ];

    $form['advanced_options']['restart_threshold_sleep'] = [
      '#type' => 'number',
      '#title' => $this->t('Restart threshold sleep'),
      '#default_value' => $this->configuration['query']['restart_threshold_sleep'],
      '#description' => $this->t('The number of seconds to sleep between stopping and re-starting the transport.'),
      '#min' => 0,
    ];

    $form['advanced_options']['ping_threshold'] = [
      '#type' => 'number',
      '#title' => $this->t('Ping threshold'),
      '#default_value' => $this->configuration['query']['ping_threshold'],
      '#description' => $this->t('The minimum number of seconds between two messages required to ping the server.'),
      '#min' => 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['user'] = $form_state->getValue('user');
    $this->configuration['use_key_module'] = (bool) $form_state->getValue('use_key_module');

    if ($this->configuration['use_key_module']) {
      // Using key module for secure storage
      $this->configuration['pass_key'] = $form_state->getValue('pass_key');
      // Clear the plain text password when switching to key storage
      $this->configuration['pass'] = '';
    }
    else {
      // Using plain text storage
      if (!empty($form_state->getValue('change_pass'))) {
        $this->configuration['pass'] = $form_state->getValue('pass');
      }
      // Clear the key reference when not using key module
      $this->configuration['pass_key'] = '';
    }

    $this->configuration['host'] = $form_state->getValue('host');
    $this->configuration['port'] = $form_state->getValue('port');
    $this->configuration['query']['verify_peer'] = $form_state->getValue('verify_peer');
    $this->configuration['query']['local_domain'] = $form_state->getValue('local_domain');
    $this->configuration['query']['restart_threshold'] = $form_state->getValue('restart_threshold');
    $this->configuration['query']['restart_threshold_sleep'] = $form_state->getValue('restart_threshold_sleep');
    $this->configuration['query']['ping_threshold'] = $form_state->getValue('ping_threshold');
  }

  /**
   * {@inheritdoc}
   */
  public function getDsn() {
    $cfg = $this->configuration;
    $default_cfg = $this->defaultConfiguration();

    // Handle password retrieval
    $password = '';
    if (!empty($cfg['use_key_module']) && !empty($cfg['pass_key'])) {
      // Get password from Key module
      if ($this->moduleHandler->moduleExists('key')) {
        try {
          $key_storage = $this->entityTypeManager->getStorage('key');
          $key = $key_storage->load($cfg['pass_key']);
          if ($key) {
            $password = $key->getKeyValue();
          }
        }
        catch (\Exception $e) {
          // Log error but continue with empty password to avoid fatal errors
          $this->logger->error('Failed to retrieve password from key @key_id: @message', [
            '@key_id' => $cfg['pass_key'],
            '@message' => $e->getMessage(),
          ]);
        }
      }
    }
    else {
      // Use plain text password
      $password = $cfg['pass'] ?? '';
    }

    // Remove default values from query string.
    $query = !empty($cfg['query']) ? array_diff_assoc($cfg['query'], $default_cfg['query']) : [];

    $dsn = $this->getPluginId() . '://' .
      (!empty($cfg['user']) ? urlencode($cfg['user']) : '') .
      (!empty($password) ? ':' . urlencode($password) : '') .
      (!empty($cfg['user']) ? '@' : '') .
      (urlencode($cfg['host'] ?? 'default')) .
      (isset($cfg['port']) ? ':' . $cfg['port'] : '') .
      ($query ? '?' . http_build_query($query) : '');

    return $dsn;
  }

}
