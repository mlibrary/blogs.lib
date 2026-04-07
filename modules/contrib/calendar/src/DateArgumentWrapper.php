<?php

namespace Drupal\calendar;

use Drupal\views\Plugin\views\argument\Date;

/**
 * The DateArgumentWrapper class.
 */
class DateArgumentWrapper {

  /**
   * The date object.
   */
  protected Date $dateArg;

  /**
   * The variable declaration of type DateTime.
   */
  protected ?\DateTime $minDate = NULL;

  /**
   * The variable declaration of type DateTime.
   */
  protected ?\DateTime $maxDate = NULL;

  /**
   * The variable declaration of type int.
   */
  protected int $position = 0;

  /**
   * Function to get the position.
   *
   * @return int
   *   Returns position.
   */
  public function getPosition(): int {
    return $this->position;
  }

  /**
   * Function to set position.
   *
   * @param int $position
   *   The position.
   */
  public function setPosition(int $position): void {
    $this->position = $position;
  }

  /**
   * The function to return date.
   *
   * @return \Drupal\views\Plugin\views\argument\Date
   *   Returns date.
   */
  public function getDateArg(): Date {
    return $this->dateArg;
  }

  /**
   * DateArgumentWrapper constructor.
   */
  public function __construct(Date $dateArg) {
    $this->dateArg = $dateArg;
  }

  /**
   * Get the argument date format for the handler.
   *
   * \Drupal\views\Plugin\views\argument\Date has no getter for
   * protected argFormat member variable until #2325899.
   */
  public function getArgFormat(): string {
    $class = get_class($this->dateArg);

    // Remove method_exists() check once committed in
    // https://www.drupal.org/project/drupal/issues/2325899#comment-15653541.
    if (method_exists($this->dateArg, 'getArgFormat')) {
      return $this->dateArg->getArgFormat();
    }

    $formats = [
      'YearMonthDate' => 'Ym',
      'FullDate' => 'Ymd',
      'YearDate' => 'Y',
      'YearWeekDate' => 'oW',
      'WeekDate' => 'W',
      'MonthDate' => 'm',
      'DayDate' => 'd',
    ];

    foreach ($formats as $classSubstring => $format) {
      if (stripos($class, $classSubstring) !== FALSE) {
        return $format;
      }
    }

    // Default if not using other core date argument classes.
    return 'Y-m-d';
  }

  /**
   * {@inheritdoc}
   */
  public function createDateTime(?string $value = NULL): ?\DateTime {
    $value = $value ?? $this->dateArg->getValue();
    if ($value === NULL || $value === '') {
      return NULL;
    }
    if (!$this->validateValue($value)) {
      return NULL;
    }
    return $this->createFromFormat($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function createFromFormat(string $value): ?\DateTime {
    $format = $this->getArgFormat();
    if ($format == 'oW') {
      $date = new \DateTime();
      $year = (int) substr($value, 0, 4);
      $month = (int) substr($value, 4, 2);
      $date->setISODate($year, $month);
    }
    else {
      // Adds a ! character to the format so that the date is reset instead of
      // using the current day info, which can lead to issues for months with
      // 31 days.
      $format = '!' . $this->getArgFormat();
      $date = \DateTime::createFromFormat($format, $value);
      if ($date === FALSE) {
        return NULL;
      }
    }
    return $date;
  }

  /**
   * {@inheritdoc}
   */
  public function format(string $format): ?string {
    if ($date = $this->createDateTime()) {
      return $date->format($format);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getGranularity() {
    // Prefer the argument plugin's explicit granularity getter when available.
    if (method_exists($this->dateArg, 'getGranularity')) {
      $granularity = $this->dateArg->getGranularity();
      if (!empty($granularity)) {
        return $granularity;
      }
    }

    // Fallback: derive the granularity from the Views plugin ID.
    // Example IDs: "datetime_full_date", "date_year_week", "datetime_year".
    $plugin_id = $this->dateArg->getPluginId();
    $plugin_granularity = str_replace('datetime_', '', $plugin_id);
    $plugin_granularity = str_replace('date_', '', $plugin_granularity);
    return match ($plugin_granularity) {
      'full_date', 'fulldate' => 'day',
      'year' => 'year',
      'year_week' => 'week',
      default => 'month',
    };
  }

  /**
   * Function to get min date.
   *
   * @return \DateTime|null
   *   Returns the minimum date as a DateTime object or NULL if not set.
   */
  public function getMinDate(): ?\DateTime {
    if (!$this->minDate) {
      $date = $this->createDateTime();
      if (!$date) {
        return NULL;
      }
      $granularity = $this->getGranularity();
      if ($granularity == 'month') {
        $date->modify("first day of this month");
      }
      elseif ($granularity == 'week') {
        $date->modify('this week');
      }
      elseif ($granularity == 'year') {
        $date->modify("first day of January");
      }
      $date->setTime(0, 0, 0);
      $this->minDate = \DateTime::createFromInterface($date);
    }
    return $this->minDate;
  }

  /**
   * Function to get max date.
   *
   * @return \DateTime|null
   *   Returns the maximum date as a DateTime object or NULL if not set.
   */
  public function getMaxDate(): ?\DateTime {
    if (!$this->maxDate) {
      $date = $this->createDateTime();
      if (!$date) {
        return NULL;
      }
      $granularity = $this->getGranularity();
      if ($granularity == 'month') {
        $date->modify("last day of this month");
      }
      elseif ($granularity == 'week') {
        $date->modify('this week +6 days');
      }
      elseif ($granularity == 'year') {
        $date->modify("last day of December");
      }
      $date->setTime(23, 59, 59);
      $this->maxDate = \DateTime::createFromInterface($date);
    }
    return $this->maxDate;
  }

  /**
   * Check if a string value is valid for this format.
   *
   * \DateTime::createFromFormat will not throw an error but try to make a date
   * \DateTime::getLastErrors() is also not reliable.
   *
   * @return bool
   *   Returns TRUE if the value is valid, FALSE otherwise.
   */
  public function validateValue(?string $value = NULL): bool {
    $value = $value ?? $this->dateArg->getValue();
    if ($value === NULL || $value === '') {
      return FALSE;
    }
    if ($this->getArgFormat() == 'oW') {
      $info = $this->getYearWeek($value);

      // If $info is not an array, $value should not be valid.
      if (!is_array($info)) {
        return FALSE;
      }

      // Find the max week for a year. Some years start a 53rd week.
      $max_week = gmdate('W', strtotime("28 December {$info['year']}"));
      return $info['week'] >= 1 && $info['week'] <= $max_week;

    }
    else {
      $created_date = $this->createFromFormat($value);
      return $created_date !== NULL && $created_date->format($this->getArgFormat()) === $value;
    }

  }

  /**
   * Check if a string value is valid for this format.
   *
   * @param string $value
   *   The date string to validate.
   *
   * @return array|bool
   *   Returns an array with 'year' and 'week' if valid, FALSE otherwise.
   */
  protected function getYearWeek(string $value): array|bool {
    if (is_numeric($value) && strlen($value) == 6) {
      $return['year'] = (int) substr($value, 0, 4);
      $return['week'] = (int) substr($value, 4, 2);
      return $return;
    }
    return FALSE;
  }

}
