<?php

namespace Drupal\calendar\Plugin\views\argument_validator;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\calendar\DateArgumentWrapper;
use Drupal\views\Plugin\views\argument\ArgumentPluginBase;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\argument_validator\ArgumentValidatorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a argument validator plugin for Date arguments used in Calendar.
 *
 * @ViewsArgumentValidator(
 *   id = "calendar",
 *   title = @Translation("Calendar Date Format"),
 * )
 */
class CalendarValidator extends ArgumentValidatorPluginBase {

  /**
   * The date argument wrapper object.
   *
   * @var \Drupal\calendar\DateArgumentWrapper
   */
  protected $argumentWrapper;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DateFormatterInterface $dateFormatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('date.formatter'));
  }

  /**
   * {@inheritdoc}
   */
  public function validateArgument($arg) {
    if (isset($this->argumentWrapper) && $this->argumentWrapper->validateValue($arg)) {
      $date = $this->argumentWrapper->createDateTime();
      // Adds 'January' to year to get correct header on Year calendars
      // to avoid problem defined on third note at
      // http://www.php.net/manual/en/datetime.formats.date.php
      $january = '';
      if ($this->options['replacement_format'] === 'Y') {
        $january = 'January';
      }
      $time = strtotime($january . $date->format($this->options['replacement_format']));

      // Override title for substitutions.
      // @see \Drupal\views\Plugin\views\argument\ArgumentPluginBase::getTitle
      $this->argument->validated_title = $this->dateFormatter->format($time, 'custom', $this->options['replacement_format']);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setArgument(ArgumentPluginBase $argument) {
    parent::setArgument($argument);
    if ($argument instanceof Date) {
      $this->argumentWrapper = new DateArgumentWrapper($argument);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['replacement_format'] = ['default' => ''];
    return $options;
  }

  /**
   * Get default format value for the options form.
   *
   * @return string
   *   The default date format based on the granularity.
   */
  protected function getDefaultReplacementFormat() {

    switch ($this->argumentWrapper->getGranularity()) {
      case 'month':
        return 'F Y';

      case 'year':
        return 'Y';

      case 'week':
        return 'F j, Y';

      case 'day':
        return 'l, F j, Y';

      default:
        // @todo Load format used for medium here
        return 'F j, Y';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    if (!isset($this->argumentWrapper)) {
      return;
    }
    // We can't set default in defineOptions because argument is not set yet.
    if ($this->options['replacement_format']) {
      $default = $this->options['replacement_format'];
    }
    else {
      $default = $this->getDefaultReplacementFormat();
    }
    $form['replacement_format'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Replacement date pattern'),
      '#default_value' => $default,
      // @todo Better description and link
      '#description' => $this->t('Provide a date pattern to be used when replace this arguments as a title.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition() {
    return new ContextDefinition('string', $this->argument->adminLabel(), FALSE);
  }

}
