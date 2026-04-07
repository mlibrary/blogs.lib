<?php

namespace Drupal\calendar;

/**
 * Defines a calendar style info object.
 */
class CalendarStyleInfo {

  /**
   * Defines whether or not this is a mini calendar.
   */
  protected bool $mini = FALSE;

  /**
   * The size of the month name.
   */
  protected int $monthNameSize = 99;

  /**
   * The size of the calendar name.
   */
  protected int $nameSize = 3;

  /**
   * Defines whether or not to display the title.
   */
  protected bool $showTitle = FALSE;

  /**
   * Defines whether or not to display the navigation.
   */
  protected bool $showNavigation = FALSE;

  /**
   * Defines whether or not to display the week numbers.
   */
  protected bool $showWeekNumbers = FALSE;

  /**
   * Defines whether or not to display empty times.
   */
  protected bool $showEmptyTimes;

  /**
   * A set of start times to group items.
   *
   * @var string[]
   */
  protected array $groupByTimes = [];

  /**
   * Defines a custom group by field.
   */
  protected string $customGroupByField = '';

  /**
   * The maximum amount of items to show.
   */
  protected int $maxItems = 0;

  /**
   * Defines what the maximum items style is.
   */
  protected string $maxItemsStyle = 'more';

  /**
   * Defines what the theme style is.
   */
  protected int $themeStyle = 1;

  /**
   * Defines what the multi day theme is.
   */
  protected int $multiDayTheme = 1;

  /**
   * Getter for the mini format variable.
   */
  public function isMini(): bool {
    return $this->mini;
  }

  /**
   * Setter for the mini format variable.
   */
  public function setMini(bool $mini): void {
    $this->mini = $mini;
  }

  /**
   * Getter for the month name size.
   */
  public function getMonthNameSize(): int {
    return $this->monthNameSize;
  }

  /**
   * Setter for the month name size.
   */
  public function setMonthNameSize(int $nameSize): void {
    $this->monthNameSize = $nameSize;
  }

  /**
   * Getter for the name size.
   */
  public function getNameSize(): int {
    return $this->nameSize;
  }

  /**
   * Setter for the name size.
   */
  public function setNameSize(int $nameSize): void {
    $this->nameSize = $nameSize;
  }

  /**
   * Getter for the show title variable.
   */
  public function isShowTitle(): bool {
    return $this->showTitle;
  }

  /**
   * Setter for the show title variable.
   */
  public function setShowTitle(bool $showTitle): void {
    $this->showTitle = $showTitle;
  }

  /**
   * Getter for the show navigation variable.
   */
  public function isShowNavigation(): bool {
    return $this->showNavigation;
  }

  /**
   * Setter for the show navigation variable.
   */
  public function setShowNavigation(bool $showNavigation): void {
    $this->showNavigation = $showNavigation;
  }

  /**
   * Getter for the show week numbers variable.
   */
  public function isShowWeekNumbers(): bool {
    return $this->showWeekNumbers;
  }

  /**
   * Setter for the show week numbers variable.
   */
  public function setShowWeekNumbers(bool $showWeekNumbers): void {
    $this->showWeekNumbers = $showWeekNumbers;
  }

  /**
   * Getter for the show empty times variable.
   */
  public function isShowEmptyTimes(): bool {
    return $this->showEmptyTimes;
  }

  /**
   * Setter for the show empty times variable.
   */
  public function setShowEmptyTimes(bool $showEmptyTimes): void {
    $this->showEmptyTimes = $showEmptyTimes;
  }

  /**
   * Getter for the group by times property.
   */
  public function getGroupByTimes(): array {
    return $this->groupByTimes;
  }

  /**
   * Setter for the group by times property.
   */
  public function setGroupByTimes(array $groupByTimes): void {
    $this->groupByTimes = $groupByTimes;
  }

  /**
   * Getter for the custom group by field variable.
   */
  public function getCustomGroupByField(): string {
    return $this->customGroupByField;
  }

  /**
   * Setter for the custom group by field variable.
   */
  public function setCustomGroupByField(string $customGroupByField): void {
    $this->customGroupByField = $customGroupByField;
  }

  /**
   * Getter for the max items to show.
   */
  public function getMaxItems(): int {
    return $this->maxItems;
  }

  /**
   * Setter for the max items variable.
   */
  public function setMaxItems(int $maxItems): void {
    $this->maxItems = $maxItems;
  }

  /**
   * Getter for the max items style.
   */
  public function getMaxItemsStyle(): string {
    return $this->maxItemsStyle;
  }

  /**
   * Setter for the maximum items style.
   */
  public function setMaxItemsStyle(string $maxItemsStyle): void {
    $this->maxItemsStyle = $maxItemsStyle;
  }

  /**
   * Getter for the multi-day theme.
   */
  public function getMultiDayTheme(): int {
    return $this->multiDayTheme;
  }

  /**
   * Setter for the multi day theme variable.
   */
  public function setMultiDayTheme(int $multiDayTheme): void {
    $this->multiDayTheme = $multiDayTheme;
  }

  /**
   * Getter for the theme style variable.
   */
  public function getThemeStyle(): int {
    return $this->themeStyle;
  }

  /**
   * Setter for the theme style variable.
   */
  public function setThemeStyle(int $themeStyle): void {
    $this->themeStyle = $themeStyle;
  }

}
