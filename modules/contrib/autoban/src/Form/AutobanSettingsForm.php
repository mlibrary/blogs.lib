<?php

namespace Drupal\autoban\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure autoban settings for this site.
 */
class AutobanSettingsForm extends ConfigFormBase {

  /**
   * The autoban object.
   *
   * @var \Drupal\autoban\Controller\AutobanController
   */
  protected $autoban;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->autoban = $container->get('autoban');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoban_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'autoban.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('autoban.settings');

    // Retrieve the Ban manager list.
    $providers = [];
    $controller = $this->autoban;
    $banManagerList = $controller->getBanProvidersList();
    if (!empty($banManagerList)) {
      foreach ($banManagerList as $id => $item) {
        $providers[$id] = $item['name'];
      }
      $form['providers'] = [
        '#markup' => sprintf('<label>%s:</label> %s', $this->t('Ban providers'), implode(', ', $providers)),
        '#allowed_tags' => ['label'],
      ];
    }
    else {
      $this->messenger()->addMessage(
        $this->t('List ban providers is empty. You have to enable at least one Autoban providers module.'),
        'warning'
      );
    }

    $thresholds = $config->get('autoban_thresholds') ?: "1\n2\n3\n5\n10\n20\n50\n100";
    $form['autoban_thresholds'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Thresholds'),
      '#default_value' => $thresholds,
      '#required' => TRUE,
      '#description' => $this->t('Thresholds set for Autoban rules threshold field.'),
    ];

    $windows = $config->get('autoban_windows') ?: "1 hour ago\n1 day ago\n1 week ago\n1 month ago\n1 year ago";
    $form['autoban_windows'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Windows'),
      '#default_value' => $windows,
      '#required' => TRUE,
      '#description' => $this->t('Relative time windows for log entries
        Autoban rules should run against. For example, a window of "1 hour ago"
        means the rule will be run against log entries that occurred since 1
        hour ago. Must be in a format that <a href="@strtotime">PHP\'s
        strtotime function</a> can interpret.', [
          '@strtotime' => 'https://php.net/manual/function.strtotime.php',
        ]),
    ];

    $windows = explode(PHP_EOL, $windows);
    $windows = array_combine($windows, $windows);
    array_walk($windows, function (&$item, $key) {
      $item = "<span>$item</span>";
    });
    $form['autoban_window_default'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Default window"),
      '#default_value' => $config->get('autoban_window_default') ?: '',
      '#description' => $this->t('If left blank, automatically
        created rules will be run against all log entries. Valid windows are:
        <br />') . implode(", ", $windows),
    ];

    $query_mode = $config->get('autoban_query_mode') ?: 'like';
    $form['autoban_query_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Query mode'),
       // phpcs:disable
      '#options' => ['like' => 'LIKE', 'regexp' => 'REGEXP'],
      // phpcs:enable
      '#default_value' => $query_mode,
      '#description' => $this->t('Use REGEXP option if your SQL engine supports REGEXP syntax.'),
    ];

    $use_wildcards = $config->get('autoban_use_wildcards') ?: FALSE;
    $form['autoban_use_wildcards'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use wildcards'),
      '#default_value' => $use_wildcards,
      '#description' => $this->t('If not checked, Autoban will add % to begin and end of message patterns.'),
    ];

    $form['autoban_whitelist'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Whitelist'),
      '#default_value' => $config->get('autoban_whitelist'),
      '#description' => $this->t('Enter a list of IP addresses or domain. Format: CIDR "aa.bb.cc.dd/ee" or "aa.bb.cc.dd" or "googlebot.com". # symbol use as a comment.
        The rows beginning with # are comments and are ignored.
        For example: <a href="http://www.iplists.com/google.txt" rel="nofollow" target="_new">robot-whitelist site</a>.'),
      '#rows' => 10,
      '#cols' => 30,
    ];

    $dblog_type_exclude = $config->get('autoban_dblog_type_exclude') ?: "autoban\ncron\nphp\nsystem\nuser";
    $form['autoban_dblog_type_exclude'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude dblog types'),
      '#default_value' => $dblog_type_exclude,
      '#description' => $this->t('Exclude dblog types events for log analyze, autoban rules.'),
    ];

    $form['autoban_threshold_analyze'] = [
      '#type' => 'number',
      '#title' => $this->t("Analyze's form threshold"),
      '#default_value' => $config->get('autoban_threshold_analyze') ?: 5,
      '#description' => $this->t('Threshold for log analyze.'),
    ];

    $form['autoban_cron'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable cron'),
      '#default_value' => $config->get('autoban_cron') ?? TRUE,
      '#description' => $this->t('If checked, Autoban will process rules on cron.'),
    ];

    $form['autoban_force_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force mode'),
      '#default_value' => $config->get('autoban_force_mode') ?? FALSE,
      '#description' => $this->t('If checked, Autoban will ban IP for every 403/404 request status.'),
    ];

    $form['autoban_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug mode'),
      '#default_value' => $config->get('autoban_debug') ?? FALSE,
      '#description' => $this->t('If checked, Autoban will show debug messages.'),
    ];

    $form['#attached']['library'][] = 'autoban/form';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $thresholds = explode(PHP_EOL, $form_state->getValue('autoban_thresholds'));
    foreach ($thresholds as $threshold) {
      $threshold = intval(trim($threshold));
      if (empty($threshold) || $threshold <= 0) {
        $form_state->setErrorByName('autoban_thresholds', $this->t('Threshold values must be a positive integer.'));
      }
    }

    $windows = explode(PHP_EOL, $form_state->getValue('autoban_windows'));
    foreach ($windows as $window) {
      $window = trim($window);
      if (empty($window) || strtotime($window) === FALSE) {
        $form_state->setErrorByName('autoban_windows', $this->t("Window values must be in a format that PHP's strtotime() function can interpret."));
      }
    }

    $window_default = trim($form_state->getValue('autoban_window_default'));
    if (!empty($window_default)) {
      array_walk($windows, function (&$item, $key) {
        $item = trim($item);
      });
      if (!in_array($window_default, $windows)) {
        $form_state->setErrorByName('autoban_window_default', $this->t("Default window for automatically created Autoban rules must be one of: @windows.", [
          '@windows' => implode(", ", $windows),
        ]));
      }
      elseif (strtotime($window_default) === FALSE) {
        $form_state->setErrorByName('autoban_window_default', $this->t("Default log entry window must be in a format that PHP's strtotime() function can interpret."));
      }
    }

    $dblog_type_exclude = explode(PHP_EOL, $form_state->getValue('autoban_dblog_type_exclude'));
    foreach ($dblog_type_exclude as $item) {
      $item = trim($item);
      if (empty($item)) {
        $form_state->setErrorByName('autoban_dblog_type_exclude', $this->t('Dblog type exclude item cannot be empty.'));
      }
    }

    if ($form_state->getValue('autoban_threshold_analyze') <= 0) {
      $form_state->setErrorByName('autoban_threshold_analyze', $this->t("Analyze's form threshold must be a positive integer."));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // To ensure that the threshold values are integers.
    $thresholds = explode(PHP_EOL, $form_state->getValue('autoban_thresholds'));
    array_walk($thresholds, function (&$item, $key) {
      $item = (int) $item;
    });

    // To ensure that the window values are trimmed.
    $windows = explode(PHP_EOL, $form_state->getValue('autoban_windows'));
    array_walk($windows, function (&$item, $key) {
      $item = trim($item);
    });

    // To ensure that the dblog_type_exclude values was trimmed.
    $dblog_type_exclude = explode(PHP_EOL, $form_state->getValue('autoban_dblog_type_exclude'));
    array_walk($dblog_type_exclude, function (&$item, $key) {
      $item = trim($item);
    });

    $this->configFactory->getEditable('autoban.settings')
      ->set('autoban_thresholds', implode(PHP_EOL, $thresholds))
      ->set('autoban_windows', implode(PHP_EOL, $windows))
      ->set('autoban_window_default', trim($form_state->getValue('autoban_window_default')))
      ->set('autoban_query_mode', $form_state->getValue('autoban_query_mode'))
      ->set('autoban_use_wildcards', $form_state->getValue('autoban_use_wildcards'))
      ->set('autoban_whitelist', $form_state->getValue('autoban_whitelist'))
      ->set('autoban_dblog_type_exclude', implode(PHP_EOL, $dblog_type_exclude))
      ->set('autoban_threshold_analyze', $form_state->getValue('autoban_threshold_analyze'))
      ->set('autoban_cron', (bool) $form_state->getValue('autoban_cron'))
      ->set('autoban_force_mode', (bool) $form_state->getValue('autoban_force_mode'))
      ->set('autoban_debug', (bool) $form_state->getValue('autoban_debug'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
