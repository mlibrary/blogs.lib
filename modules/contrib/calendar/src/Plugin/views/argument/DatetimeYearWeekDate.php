<?php

namespace Drupal\calendar\Plugin\views\argument;

use Drupal\views\Attribute\ViewsArgument;
use Drupal\datetime\Plugin\views\argument\Date;

/**
 * Argument handler for a day.
 */
#[ViewsArgument('datetime_year_week')]
class DatetimeYearWeekDate extends Date {

  /**
   * {@inheritdoc}
   */
  protected $argFormat = 'oW';

}
