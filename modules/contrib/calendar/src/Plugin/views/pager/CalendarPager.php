<?php

namespace Drupal\calendar\Plugin\views\pager;

use Drupal\calendar\CalendarHelper;
use Drupal\calendar\DateArgumentWrapper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsPager;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\pager\PagerPluginBase;
use Drupal\views\ViewExecutable;

/**
 * The plugin to handle calendar pager.
 *
 * @ingroup views_pager_plugins
 */
#[ViewsPager(
  id: 'calendar',
  title: new TranslatableMarkup('Calendar Pager'),
  short_title: new TranslatableMarkup('Calendar'),
  help: new TranslatableMarkup('Calendar Pager'),
  theme: 'calendar_pager',
  register_theme: FALSE,
)]
class CalendarPager extends PagerPluginBase {
  use LoggerChannelTrait;

  const NEXT = '+';
  const PREVIOUS = '-';

  /**
   * The Date argument wrapper object.
   */
  protected ?DateArgumentWrapper $argument = NULL;

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
   */
  protected function getPagerArgValue(string $mode): string {
    $datetime = $this->argument->createDateTime();
    if (!$datetime) {
      $view_id = $this->view->id() ?? $this->view->storage->id();
      $this->getLogger('calendar')->notice('Unable to build calendar pager argument from contextual filter value for view %view.', [
        '%view' => $view_id ?? 'unknown',
      ]);

      // Fallback to the raw argument value to avoid rendering pager errors.
      return (string) ($this->argument->getDateArg()->getValue() ?? '');
    }
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
   */
  protected function getPagerUrl(string $mode, array $input): GeneratedUrl|string {
    $value = $this->getPagerArgValue($mode);
    $current_position = 0;
    $args = [];
    foreach ($this->view->argument as $handler) {
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
   * {@inheritdoc}
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
