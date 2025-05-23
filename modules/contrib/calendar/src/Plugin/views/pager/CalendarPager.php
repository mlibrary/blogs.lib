<?php

namespace Drupal\calendar\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\calendar\CalendarHelper;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\ViewExecutable;

/**
 * The plugin to handle calendar pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "calendar",
 *   title = @Translation("Calendar Pager"),
 *   short_title = @Translation("Calendar"),
 *   help = @Translation("Calendar Pager"),
 *   theme = "calendar_pager",
 *   register_theme = FALSE
 * )
 */
class CalendarPager extends PagerPluginBase {

  const NEXT = '+';
  const PREVIOUS = '-';
  /**
   * The Date argument wrapper object.
   *
   * @var \Drupal\calendar\DateArgumentWrapper
   */
  protected $argument;

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->argument = CalendarHelper::getDateArgumentHandler($this->view);
    $this->setItemsPerPage(0);
  }

  /**
   * {@inheritdoc}
   */
  public function render($input) {
    // The $this->argument may be FALSE.
    if (!$this->argument || !$this->argument->validateValue()) {
      return [];
    }
    $items['previous'] = [
      'url' => $this->getPagerUrl(self::PREVIOUS, $input),
    ];
    $items['next'] = [
      'url' => $this->getPagerUrl(self::NEXT, $input),
    ];
    return [
      '#theme' => $this->themeFunctions(),
      '#items' => $items,
      '#exclude' => $this->options['exclude_display'],
    ];
  }

  /**
   * Get the date argument value for the pager link.
   *
   * @param string $mode
   *   Either '-' or '+' to determine which direction.
   *
   * @return string
   *   Formatted date time.
   */
  protected function getPagerArgValue($mode) {
    $datetime = $this->argument->createDateTime();
    $datetime->modify($mode . '1 ' . $this->argument->getGranularity());
    return $datetime->format($this->argument->getArgFormat());
  }

  /**
   * Get the href value for the pager link.
   *
   * @param string $mode
   *   Either '-' or '+' to determine which direction.
   * @param array $input
   *   Any extra GET parameters that should be retained, such as exposed
   *   input.
   *
   * @return string
   *   Url.
   */
  protected function getPagerUrl($mode, array $input) {
    $value = $this->getPagerArgValue($mode);
    $current_position = 0;
    $args = [];
    /**
     * @var \Drupal\views\Plugin\views\argument\ArgumentPluginBase $handler
     */
    foreach ($this->view->argument as $name => $handler) {
      if ($current_position != $this->argument->getPosition()) {
        $args["arg_$current_position"] = $handler->getValue();
      }
      else {
        $args["arg_$current_position"] = $value;
      }
      $current_position++;
    }

    $display_handler = $this->view->displayHandlers->get($this->view->current_display)
      ->getRoutedDisplay();
    if ($display_handler) {
      $url = $this->view->getUrl($args, $this->view->current_display);
    }
    else {
      $url = Url::fromRoute('<current>', [], []);
    }

    if (!empty($input)) {
      $url->setOption('query', $input);
    }

    return $url->toString();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['exclude_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude from Display'),
      '#default_value' => $this->options['exclude_display'],
      '#description' => $this->t('Use this option if you only want to display the pager in Calendar Header area.'),
    ];
  }

  /**
   * Returns a string to display as the clickable title for the pager plugin.
   */
  public function summaryTitle() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['exclude_display'] = ['default' => FALSE];

    return $options;
  }

}
