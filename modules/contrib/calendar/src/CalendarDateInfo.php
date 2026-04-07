<?php

namespace Drupal\calendar;

use Drupal\views\Plugin\views\argument\Date;

/**
 * Defines a calendar date info object.
 */
class CalendarDateInfo {

  /**
   * The calendar type.
   */
  protected string $calendarType;

  /**
   * The date argument.
   */
  protected Date $dateArgument;

  /**
   * The timezone information for this calendar.
   */
  protected \DateTimeZone $timezone;

  /**
   * The granularity of this calendar (e.g. 'day', 'week').
   */
  protected string $granularity;

  /**
   * The range of this calendar (e.g. '-3:+3').
   */
  protected string $range;

  /**
   * The minimum date of this calendar.
   */
  protected \DateTimeInterface $minDate;

  /**
   * The minimum year of this calendar.
   */
  protected string $minYear;

  /**
   * The minimum month of this calendar.
   */
  protected string $minMonth;

  /**
   * The minimum day of this calendar.
   */
  protected string $minDay;

  /**
   * The minimum week number of this calendar.
   */
  protected int $minWeek;

  /**
   * The maximum date of this calendar.
   */
  protected \DateTimeInterface $maxDate;

  /**
   * Getter for the calendar type.
   */
  public function getCalendarType(): string {
    return $this->calendarType;
  }

  /**
   * Setter for the calendar type.
   */
  public function setCalendarType(string $calendarType): void {
    $this->calendarType = $calendarType;
  }

  /**
   * Getter for the date argument.
   */
  public function getDateArgument(): Date {
    return $this->dateArgument;
  }

  /**
   * Setter for the date argument.
   */
  public function setDateArgument($dateArgument): void {
    $this->dateArgument = $dateArgument;
  }

  /**
   * Getter for the timezone variable.
   */
  public function getTimezone(): \DateTimeZone {
    return $this->timezone;
  }

  /**
   * Setter for the timezone variable.
   */
  public function setTimezone(\DateTimeZone $timezone): void {
    $this->timezone = $timezone;
  }

  /**
   * Getter for the calendar granularity.
   */
  public function getGranularity(): string {
    return $this->granularity;
  }

  /**
   * Setter for the granularity.
   */
  public function setGranularity(string $granularity): void {
    $this->granularity = $granularity;
  }

  /**
   * Getter for the range.
   */
  public function getRange(): string {
    return $this->range;
  }

  /**
   * Setter for the range.
   */
  public function setRange(string $range): void {
    $this->range = $range;
  }

  /**
   * Getter for the minimum date.
   */
  public function getMinDate(): \DateTimeInterface {
    return $this->minDate;
  }

  /**
   * Setter for the minimum date.
   */
  public function setMinDate(\DateTimeInterface $minDate): void {
    $this->minDate = $minDate;
  }

  /**
   * Getter for the minimum year.
   */
  public function getMinYear(): string {
    return $this->minYear;
  }

  /**
   * Setter for the minimum year.
   */
  public function setMinYear(string $minYear): void {
    $this->minYear = $minYear;
  }

  /**
   * Getter for the minimum month.
   */
  public function getMinMonth(): string {
    return $this->minMonth;
  }

  /**
   * Setter for the minimum month.
   */
  public function setMinMonth(string $minMonth): void {
    $this->minMonth = $minMonth;
  }

  /**
   * Getter for the minimum day.
   */
  public function getMinDay(): string {
    return $this->minDay;
  }

  /**
   * Setter for the minimum day.
   */
  public function setMinDay(string $minDay): void {
    $this->minDay = $minDay;
  }

  /**
   * Getter for the minimum week number.
   */
  public function getMinWeek(): int {
    return $this->minWeek;
  }

  /**
   * Setter for the minimum week number.
   */
  public function setMinWeek(int $minWeek): void {
    $this->minWeek = $minWeek;
  }

  /**
   * Getter for the maximum date.
   */
  public function getMaxDate(): \DateTimeInterface {
    return $this->maxDate;
  }

  /**
   * Setter for the maximum date.
   */
  public function setMaxDate(\DateTimeInterface $maxDate): void {
    $this->maxDate = $maxDate;
  }

}
