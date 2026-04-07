<?php

namespace Drupal\calendar;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Defines a calendar event object.
 */
class CalendarEvent {

  /**
   * Unique identifier for the rendered event.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public string $date_id = '';

  /**
   * CSS classes that should be applied to the event wrapper.
   */
  public string $class = '';

  /**
   * The indent level of the event within the rendered list.
   */
  public int $indent = 0;

  /**
   * The current depth of the event within the overlap tree.
   */
  public int $depth = 0;

  /**
   * The maximum depth reached when rendering the event hierarchy.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public int $max_depth = 1;

  /**
   * Whether the event continues from a previous day.
   */
  public bool $continuation = FALSE;

  /**
   * Whether the event continues beyond the current day.
   */
  public bool $continues = FALSE;

  /**
   * Storage for additional properties assigned dynamically in legacy code.
   *
   * @var array<string, mixed>
   */
  protected array $legacyProperties = [];

  /**
   * Calendar-aware start date for legacy property access.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public ?\DateTime $calendar_start_date = NULL;

  /**
   * Calendar-aware end date for legacy property access.
   */
  // phpcs:ignore Drupal.NamingConventions.ValidVariableName.LowerCamelName
  public ?\DateTime $calendar_end_date = NULL;

  /**
   * Legacy recurrence field item reference.
   */
  public ?FieldItemInterface $recurring = NULL;


  /**
   * The start date of the event.
   */
  protected ?\DateTimeInterface $startDate = NULL;

  /**
   * The end date of the event.
   */
  protected ?\DateTimeInterface $endDate = NULL;

  /**
   * The granularity of this event (e.g. "day", "second").
   */
  protected string $granularity = '';

  /**
   * Defines whether or not this event's duration is all day.
   */
  protected bool $allDay = FALSE;

  /**
   * The timezone of the event.
   */
  protected \DateTimeZone $timezone;

  /**
   * An array of the fields to render.
   *
   * @var array<string, mixed>
   */
  protected array $renderedFields = [];

  /**
   * The array of labels to be used for this stripe option.
   *
   * @var string[]
   */
  protected array $stripeLabels = [];

  /**
   * The hex code array of the color to be used.
   *
   * @var string[]
   */
  protected array $stripeHexes = [];

  /**
   * Whether this event covers multiple days.
   */
  public bool $isMultiDay = FALSE;

  /**
   * CalendarEvent constructor.
   */
  public function __construct(protected ContentEntityInterface $entity) {
  }

  /**
   * Getter for the entity id.
   */
  public function getEntityId(): string|int|null {
    return $this->entity->id();
  }

  /**
   * Getter for the entity type id.
   */
  public function getEntityTypeId(): string {
    return $this->entity->getEntityTypeId();
  }

  /**
   * Function to get entity.
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * Getter for the type.
   */
  public function getType(): string {
    return $this->entity->getEntityTypeId();
  }

  /**
   * {@inheritdoc}
   */
  public function getBundle(): string {
    return $this->entity->bundle();
  }

  /**
   * Getter for the start date.
   */
  public function getStartDate(): ?\DateTimeInterface {
    return $this->startDate;
  }

  /**
   * Setter for the start date.
   */
  public function setStartDate(\DateTimeInterface $startDate): void {
    $this->startDate = $startDate;
  }

  /**
   * Getter for the end date.
   */
  public function getEndDate(): ?\DateTimeInterface {
    return $this->endDate;
  }

  /**
   * Setter for the end date.
   */
  public function setEndDate(\DateTimeInterface $endDate): void {
    $this->endDate = $endDate;
  }

  /**
   * Getter for the event granularity.
   */
  public function getGranularity(): string {
    return $this->granularity;
  }

  /**
   * Setter for the event granularity.
   */
  public function setGranularity(string $granularity): void {
    $this->granularity = $granularity;
  }

  /**
   * Getter for the all day property.
   */
  public function isAllDay(): bool {
    return $this->allDay;
  }

  /**
   * Setter for the all day property.
   */
  public function setAllDay(bool $allDay): void {
    $this->allDay = $allDay;
  }

  /**
   * Getter for the timezone property.
   */
  public function getTimezone(): \DateTimeZone {
    return $this->timezone;
  }

  /**
   * Setter for the timezone property.
   */
  public function setTimezone(\DateTimeZone $timezone): void {
    $this->timezone = $timezone;
  }

  /**
   * Get the title of the event.
   */
  public function getTitle(): string|TranslatableMarkup|null {
    return $this->entity->label();
  }

  /**
   * Getter for the url.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getUrl(): Url {
    return $this->entity->toUrl();
  }

  /**
   * Getter for the rendered fields array.
   */
  public function getRenderedFields(): array {
    return $this->renderedFields;
  }

  /**
   * Setter for the rendered fields array.
   */
  public function setRenderedFields(array $renderedFields): void {
    $this->renderedFields = $renderedFields;
  }

  /**
   * Getter for the stripe label array.
   */
  public function getStripeLabels(): array {
    return $this->stripeLabels;
  }

  /**
   * Setter for the stripe label array.
   */
  public function setStripeLabels(array $stripeLabels): void {
    $this->stripeLabels = $stripeLabels;
  }

  /**
   * Getter for the stripe hex code array.
   *
   * If no array is defined, this initializes the variable to an empty array.
   */
  public function getStripeHexes(): array {
    if (!isset($this->stripeHexes)) {
      $this->stripeHexes = [];
    }
    return $this->stripeHexes;
  }

  /**
   * The setter for the stripe hex code array.
   */
  public function setStripeHexes(array $stripeHexes): void {
    $this->stripeHexes = $stripeHexes;
  }

  /**
   * Add a single strip hex.
   */
  public function addStripeHex(string $stripeHex): void {
    $this->stripeHexes[] = $stripeHex;
  }

  /**
   * Add a single strip label.
   */
  public function addStripeLabel(string $stripeLabel): void {
    $this->stripeLabels[] = $stripeLabel;
  }

  /**
   * Magic getter maintaining backwards compatibility for legacy property names.
   */
  public function __get(string $name): mixed {
    if (property_exists($this, $name)) {
      return $this->$name;
    }
    return $this->legacyProperties[$name] ?? NULL;
  }

  /**
   * Magic setter maintaining backwards compatibility for legacy property names.
   */
  public function __set(string $name, mixed $value): void {
    if (property_exists($this, $name)) {
      $this->$name = $value;
      return;
    }
    $this->legacyProperties[$name] = $value;
  }

  /**
   * Magic isset handler for legacy property names.
   */
  public function __isset(string $name): bool {
    if (property_exists($this, $name)) {
      return $this->$name !== NULL;
    }
    return array_key_exists($name, $this->legacyProperties);
  }

}
