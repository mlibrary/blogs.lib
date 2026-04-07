<?php

namespace Drupal\calendar\Plugin\views\row;

use Drupal\calendar\CalendarDateInfo;
use Drupal\calendar\CalendarEvent;
use Drupal\calendar\CalendarHelper;
use Drupal\calendar\CalendarViewsTrait;
use Drupal\date_recur\Plugin\Field\FieldType\DateRecurItem;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\views\Attribute\ViewsRow;
use Drupal\views\Plugin\views\argument\Date;
use Drupal\views\Plugin\views\argument\Formula;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin that creates a view and formats it as a Calendar entity.
 *
 * @phpstan-property \Drupal\views\ViewExecutable&object{dateInfo:\Drupal\calendar\CalendarDateInfo} $view
 */
#[ViewsRow(
  id: 'calendar_row',
  title: new TranslatableMarkup('Calendar entities'),
  help: new TranslatableMarkup('Display the content as calendar entities.'),
  display_types: ['normal'],
  theme: 'views_view_fields',
  register_theme: FALSE
)]
class Calendar extends RowPluginBase {

  use CalendarViewsTrait;
  use StringTranslationTrait;

  public const CALENDAR_EMPTY_STRIPE = '#ffffff';

  /**
   * {@inheritdoc}
   */
  protected $usesFields = TRUE;

  /**
   * Returns views_view_fields options with safe defaults.
   */
  protected function getViewsFieldsOptions(): array {
    $defaults = [
      'inline' => [],
      'separator' => '',
      'hide_empty' => FALSE,
      'default_field_elements' => TRUE,
    ];

    return $this->options + $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    return [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#options' => $this->getViewsFieldsOptions(),
      '#row' => $row,
      '#field_alias' => $this->field_alias ?? '',
    ];
  }

  /**
   * Tracks rendered entity IDs to avoid duplicate rendering.
   *
   * @var int[]
   */
  protected array $renderedEntityIds = [];

  /**
   * The entity type being handled in the preRender() function.
   */
  protected ?string $entityType;

  /**
   * The entities variable declaration.
   *
   * @var object[]
   *   The entities loaded in the preRender() function.
   */
  protected array $entities = [];

  /**
   * The date fields variable declaration.
   *
   * @var string[]
   *   An array of date fields used in the calendar.
   */
  protected array $dateFields = [];

  /**
   * The formula object.
   */
  protected Formula $dateArgument;

  /**
   * Constructs a Calendar row plugin object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected DateFormatterInterface $dateFormatter,
    protected EntityFieldManagerInterface $fieldManager,
    protected ConfigFactoryInterface $configFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $bundleInfo,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('date.formatter'),
      $container->get('entity_field.manager'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['date_fields'] = ['default' => []];
    $options['colors'] = [
      'contains' => [
        'legend' => ['default' => ''],
        'calendar_colors_type' => ['default' => []],
        'taxonomy_field' => ['default' => ''],
        'calendar_colors_vocabulary' => ['default' => []],
        'calendar_colors_taxonomy' => ['default' => []],
      ],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['markup'] = [
      '#markup' => $this->t("The calendar row plugin will format view results as calendar items. Make sure this display has a 'Calendar' format and uses a 'Date' contextual filter, or this plugin will not work correctly."),
    ];

    $form['colors'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Legend Colors'),
      '#description' => $this->t('Set a hex color value (like #ffffff) to use in the calendar legend for each content type. Items with empty values will have no stripe in the calendar and will not be added to the legend.'),
    ];

    $options = [];
    if ($this->view->getBaseTables()['node_field_data']) {
      $options['type'] = $this->t('Based on Content Type');
    }
    if ($this->moduleHandler->moduleExists('taxonomy')) {
      $options['taxonomy'] = $this->t('Based on Taxonomy');
    }

    // If no option is available, stop here.
    if (empty($options)) {
      return;
    }

    $form['colors']['legend'] = [
      '#title' => $this->t('Stripes'),
      '#description' => $this->t('Add stripes to calendar items.'),
      '#type' => 'select',
      '#options' => $options,
      '#empty_value' => (string) $this->t('None'),
      '#default_value' => $this->options['colors']['legend'],
    ];

    if ($this->view->getBaseTables()['node_field_data']) {
      $colors = $this->options['colors']['calendar_colors_type'];
      $type_names = array_map(function ($bundle_info) {
        return $bundle_info['label'];
      }, $this->bundleInfo->getBundleInfo('node'));
      foreach ($type_names as $key => $name) {
        $form['colors']['calendar_colors_type'][$key] = [
          '#title' => $name,
          '#default_value' => $colors[$key] ?? static::CALENDAR_EMPTY_STRIPE,
          '#dependency' => ['edit-row-options-colors-legend' => ['type']],
          '#type' => 'textfield',
          '#size' => 7,
          '#maxlength' => 7,
          '#element_validate' => [[$this, 'validateHexColor']],
          '#prefix' => '<div class="calendar-colorpicker-wrapper">',
          '#suffix' => '<div class="calendar-colorpicker"></div></div>',
          '#attributes' => ['class' => ['edit-calendar-colorpicker']],
          '#attached' => [
            'library' => [
              'calendar/calendar.colorpicker',
            ],
          ],
        ] + $this->visibleOnLegendState('type');
      }
    }

    if ($this->moduleHandler->moduleExists('taxonomy')) {
      // Get the display's field names of taxonomy fields.
      $vocabulary_field_options = [];
      $fields = $this->displayHandler->getOption('fields');
      foreach ($fields as $name => $field_info) {
        // Select the proper field type.
        if ($this->isTermReferenceField($field_info, $this->fieldManager)) {
          $vocabulary_field_options[$name] = $field_info['label'] ?: $name;
        }
      }
      if (empty($vocabulary_field_options)) {
        return;
      }
      $form['colors']['taxonomy_field'] = [
        '#title' => $this->t('Term field'),
        '#type' => 'select',
        '#default_value' => $this->options['colors']['taxonomy_field'],
        '#empty_value' => (string) $this->t('None'),
        '#description' => $this->t("Select the taxonomy term field to use when setting stripe colors. This works best for vocabularies with only a limited number of possible terms."),
        '#options' => $vocabulary_field_options,
      ] + $this->visibleOnLegendState('taxonomy');

      if (count($vocabulary_field_options) === 0) {
        $form['colors']['taxonomy_field']['#options'] = ['' => ''];
        $form['colors']['taxonomy_field']['#suffix'] = $this->t('You must add a term field to this view to use taxonomy stripe values. This works best for vocabularies with only a limited number of possible terms.');
      }

      // Get the Vocabulary names.
      $vocab_vids = [];
      foreach ($vocabulary_field_options as $field_name => $label) {
        $field_configs = $this->entityTypeManager->getStorage('field_config')->loadByProperties([
          'field_name' => $field_name,
        ]);
        $field_config = reset($field_configs);
        if (!$field_config) {
          continue;
        }

        $data = $this->configFactory->get('field.field.' . $field_config->getOriginalId())->getRawData();
        if (!empty($data['settings']['handler_settings']['target_bundles'])) {
          $vocab_vids[$field_name] = array_key_first($data['settings']['handler_settings']['target_bundles']);
        }
      }

      if (empty($vocab_vids)) {
        return;
      }

      $this->options['colors']['calendar_colors_vocabulary'] = $vocab_vids;

      $form['colors']['calendar_colors_vocabulary'] = [
        '#title' => $this->t('Vocabulary Legend Types'),
        '#type' => 'value',
        '#value' => $vocab_vids,
      ] + $this->visibleOnLegendState('taxonomy');

      // Get the Vocabulary term id's and map to colors.
      $term_colors = $this->options['colors']['calendar_colors_taxonomy'];
      foreach ($vocab_vids as $field_name => $vid) {
        $vocab = $this->entityTypeManager->getStorage("taxonomy_term")->loadTree($vid);
        foreach ($vocab as $term) {
          $form['colors']['calendar_colors_taxonomy'][$term->tid] = [
            '#title' => $this->t('@term_name', ['@term_name' => $term->label()]),
            '#default_value' => $term_colors[$term->tid] ?? static::CALENDAR_EMPTY_STRIPE,
            '#access' => count($vocabulary_field_options) > 0,
            '#dependency_count' => 2,
            '#dependency' => [
              'edit-row-options-colors-legend' => ['taxonomy'],
              'edit-row-options-colors-taxonomy-field' => [$field_name],
            ],
            '#type' => 'textfield',
            '#size' => 7,
            '#maxlength' => 7,
            '#element_validate' => [[$this, 'validateHexColor']],
            '#prefix' => '<div class="calendar-colorpicker-wrapper">',
            '#suffix' => '<div class="calendar-colorpicker"></div></div>',
            '#attributes' => ['class' => ['edit-calendar-colorpicker']],
            '#attached' => [
              'library' => [
                'calendar/calendar.colorpicker',
              ],
            ],
          ] + $this->visibleOnLegendState('taxonomy');
        }
      }

    }

  }

  /**
   * Check to make sure the user has entered a valid 6 digit hex color.
   *
   * @param array $element
   *   Element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State of the form.
   */
  public function validateHexColor(array $element, FormStateInterface $form_state) {
    if (!$element['#required'] && empty($element['#value'])) {
      return;
    }
    if (!preg_match('/^#(?:(?:[a-f\d]{3}){1,2})$/i', $element['#value'])) {
      $form_state->setError($element, $this->t("'@color' is not a valid hex color", ['@color' => $element['#value']]));
    }
    else {
      $form_state->setValueForElement($element, $element['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    // Preload each entity used in this view from the cache. This provides all
    // the entity values relatively cheaply, and we don't need to do it
    // repeatedly for the same entity if there are multiple results for one
    // entity.
    $ids = [];
    /** @var \Drupal\views\ResultRow $row */
    foreach ($result as $row) {
      // Use the entity id as the key so we don't create more than one value per
      // entity.
      $entity = $row->_entity;

      // Node revisions need special loading.
      if (isset($this->view->getBaseTables()['node_revision'])) {
        $this->entities[$entity->id()] = $this->entityTypeManager
          ->getStorage('node')
          ->loadRevision($entity->id());
      }
      else {
        $ids[$entity->id()] = $entity->id();
      }
    }
    $base_tables = $this->view->getBaseTables();
    $base_table = key($base_tables);
    $table_data = Views::viewsData()->get($base_table);
    $this->entityType = $table_data['table']['entity type'];

    if (!empty($ids)) {
      $this->entities = $this->entityTypeManager
        ->getStorage($this->entityType)
        ->loadMultiple($ids);
    }

    // Identify the date argument and fields that apply to this view. Preload
    // the Date Views field info for each field, keyed by the field name, so we
    // know how to retrieve field values from the cached node.
    /** @var \Drupal\views\Plugin\views\argument\Formula $handler */
    foreach ($this->view->getDisplay()->getHandlers('argument') as $handler) {
      if ($handler instanceof Date) {
        $data = CalendarHelper::dateViewFields($this->entityType);
        if ($handler->relationship) {
          $data = CalendarHelper::dateViewFields($handler->table) + $data;
        }
        $fieldName = $handler->realField;
        $alias = $handler->table . '.' . $fieldName;
        if (!isset($data[$alias])) {
          // If we cannot resolve metadata for this handler, skip it gracefully.
          // Avoids notices with relationship-backed contextual filters.
          continue;
        }
        $info = $data[$alias];
        $field_name = str_replace(['_end_value', '_value'], '', $info['real_field_name']);

        $this->dateArgument = $handler;
        $this->dateFields[$field_name] = $field_name;
      }
    }
  }

  /**
   * Builds calendar events for a single view row.
   *
   * @param object $row
   *   A single row of the query result.
   *
   * @return \Drupal\calendar\CalendarEvent[]
   *   Calendar events derived from the row.
   */
  public function buildCalendarEvents($row): array {
    $id = $row->_entity->id();
    if (!is_numeric($id) || $this->isEntityAlreadyRendered($id)) {
      return [];
    }

    $view = $this->dateArgument->view;
    /** @phpstan-var object{dateInfo:\Drupal\calendar\CalendarDateInfo} $view */
    $dateInfo = $view->dateInfo;
    $rows = [];
    $dateRecurEnabled = $this->moduleHandler->moduleExists('date_recur');

    // There could be more than one date field in a view so iterate through all
    // of them to find the right values for this view result.
    foreach ($this->dateFields as $field_name) {

      // Clone this entity so we can change it's values without altering other
      // occurrences of this entity on the same page, for example in an
      // "Upcoming" block.
      /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
      $entity = clone($this->entities[$id]);

      if (!$entity) {
        return [];
      }

      $event = new CalendarEvent($entity);

      // Retrieve the field value(s) that matched our query
      // from the cached node. Find the date and set it to the right timezone.
      $item_start_date = NULL;
      $item_end_date   = NULL;

      $entity_field_name = str_replace('_value', '', $dateInfo->getDateArgument()->realField);
      $field_definition = $entity->getFieldDefinition($entity_field_name);

      // Default timestamp storage for most base fields.
      $datetime_type = $field_definition->getSetting('datetime_type');
      $storage_format = match ($datetime_type) {
        DateTimeItem::DATETIME_TYPE_DATE => DateTimeItemInterface::DATE_STORAGE_FORMAT,
        DateTimeItem::DATETIME_TYPE_DATETIME => DateTimeItemInterface::DATETIME_STORAGE_FORMAT,
        default => 'U',
      };
      $field_item_list = $entity->get($field_name);
      $timezone = new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE);
      foreach ($field_item_list as $delta => $field_item) {
        $item = $field_item->getValue();
        // For each date on the entity create a new event in the calendar.
        $event = clone $event;
        if (isset($item)) {
          $item_start_date = \DateTime::createFromFormat($storage_format, $item['value'], $timezone);
        }
        if (isset($item) && !empty($item['end_value'])) {
          $item_end_date = \DateTime::createFromFormat($storage_format, $item['end_value'], $timezone);
        }
        else {
          $item_end_date = $item_start_date;
        }
        $date_id = 'calendar.' . $id . '.' . $field_name . '.' . $delta;

        // If we don't have a date value, go no further.
        if (empty($item_start_date)) {
          continue;
        }

        $item_start_date->setTimezone($dateInfo->getTimezone());
        $item_end_date->setTimezone($dateInfo->getTimezone());
        $event->setStartDate($item_start_date);
        $event->setEndDate($item_end_date);
        $event->setTimezone($dateInfo->getTimezone());
        $event->setGranularity($dateInfo->getGranularity());

        // All calendar row plugins should provide
        // a date_id that the theme can use.
        $event->date_id = $date_id;

        // For recurring dates pass original argument.
        // We use this in explode function.
        $recur_date_field = $field_item_list->getFieldDefinition()->getType() === 'date_recur';
        if ($dateRecurEnabled && $recur_date_field && method_exists($field_item, 'getHelper')) {
          $event->recurring = $field_item;
        }

        // We are working with an array of partially rendered items
        // as we process the calendar, so we can group and organize them.
        // At the end of our processing we'll need to swap in the fully
        // formatted display of the row. We save it here and switch it in
        // template_preprocess_calendar_item().
        /** @var \Drupal\calendar\CalendarEvent[] $events */
        $events = $this->splitEventByDay($event, $dateInfo);
        foreach ($events as $event) {
          switch ($this->options['colors']['legend']) {
            case 'type':
              if ($event->getEntityTypeId() == 'node') {
                $this->nodeTypeStripe($event);
              }

              break;

            case 'taxonomy':
              $this->calendarTaxonomyStripe($event);
              break;
          }
          $rows[] = $event;
        }
      }
    }

    return $rows;
  }

  /**
   * Check and mark if an entity ID has already been rendered.
   *
   * This addresses the issue where an entity is duplicated on a calendar
   * if it has multiple entity references. Ensures each entity is only
   * rendered once.
   */
  protected function isEntityAlreadyRendered(int $id): bool {
    if (in_array($id, $this->renderedEntityIds, TRUE)) {
      return TRUE;
    }
    $this->renderedEntityIds[] = $id;

    return FALSE;
  }

  /**
   * Splits a multi-day event into one calendar row per day.
   *
   * @throws \Exception
   */
  public function splitEventByDay(CalendarEvent $event, CalendarDateInfo $dateInfo): array {
    $rows = [];
    $events = [];
    $dateRecurItemAvailable = class_exists(DateRecurItem::class);
    if ($dateRecurItemAvailable && $event->recurring instanceof DateRecurItem) {
      $occurrenceHandler = $event->recurring->getHelper();
      $occurrences = $occurrenceHandler->getOccurrences($dateInfo->getMinDate(), $dateInfo->getMaxDate(), NULL);
      foreach ($occurrences as $occurrence) {
        $event_recur = clone $event;
        $event_recur->setStartDate($occurrence->getStart());
        $event_recur->setEndDate($occurrence->getEnd());
        $events[] = $event_recur;
      }
    }
    else {
      $events = [$event];
    }

    foreach ($events as $event) {
      $rows = array_merge($rows, $this->buildEventRowsForDays($event, $dateInfo));
    }

    return $rows;
  }

  /**
   * Builds one calendar row per day covered by the event.
   */
  protected function buildEventRowsForDays(CalendarEvent $event, CalendarDateInfo $dateInfo): array {
    $rows = [];
    $startDateObject = $event->getStartDate();
    if (!$startDateObject) {
      return [];
    }
    $endDateObject = $event->getEndDate() ?? $startDateObject;
    $item_start_date = $startDateObject->getTimestamp();
    $item_end_date = $endDateObject->getTimestamp();

    // Now that we have an 'entity' for each view result, we need to remove
    // anything outside the view date range, and possibly create additional
    // nodes so that we have a 'node' for each day that this item occupies in
    // this view.
    $now = $startDateObject->format('Y-m-d');
    $to = $endDateObject->format('Y-m-d');
    $next = new \DateTime();
    $next->setTimestamp($startDateObject->getTimestamp());

    if ($dateInfo->getTimezone()->getName() != $event->getTimezone()->getName()) {
      // Make $start and $end (derived from $node) use the timezone $to_zone,
      // just as the original dates do.
      $next->setTimezone($event->getTimezone());
    }

    if ($now > $to) {
      $to = $now;
    }

    // $now and $next are midnight (in display timezone) on
    // the first day where node will occur.
    // $to is midnight on the last day where node will occur.
    // All three were limited by the min-max date range of the view.
    $position = 0;
    while (!empty($now) && $now <= $to) {
      $entity = clone($event);

      // Get start and end of current day.
      $start = $this->dateFormatter->format($next->getTimestamp(), 'custom', 'Y-m-d H:i:s');
      $next->setTimestamp(strtotime(' +1 day -1 second', $next->getTimestamp()));
      $end = $this->dateFormatter->format($next->getTimestamp(), 'custom', 'Y-m-d H:i:s');

      // Get start and end of item, formatted the same way.
      $item_start = $this->dateFormatter->format($item_start_date, 'custom', 'Y-m-d H:i:s');
      $item_end = $this->dateFormatter->format($item_end_date, 'custom', 'Y-m-d H:i:s');

      // Get intersection of current day and the node value's duration (as
      // strings in $to_zone timezone).
      $start_string = max($item_start, $start);
      $end_string = !empty($item_end) ? (min($item_end, $end)) : NULL;
      $entity->calendar_start_date = (new \DateTime($start_string));
      $entity->calendar_end_date = $end_string ? (new \DateTime($end_string)) : NULL;

      $granularity = 'day';
      $increment = 1;
      $entity->setAllDay(CalendarHelper::dateIsAllDay($entity->getStartDate()->format('Y-m-d H:i:s'), $entity->getEndDate()->format('Y-m-d H:i:s'), $granularity, $increment));

      $calendar_start = $entity->calendar_start_date ? $this->dateFormatter->format($entity->calendar_start_date->getTimestamp(), 'custom', 'Y-m-d H:i:s') : '';

      if (!empty($calendar_start)) {
        $entity->date_id .= '.' . $position;
        $rows[] = $entity;
      }
      unset($entity);

      $next->setTimestamp(strtotime('+1 second', $next->getTimestamp()));
      $now = $this->dateFormatter->format($next->getTimestamp(), 'custom', 'Y-m-d');
      $position++;
    }
    return $rows;
  }

  /**
   * Create a stripe base on node type.
   *
   * @param \Drupal\calendar\CalendarEvent $event
   *   The event result object.
   */
  public function nodeTypeStripe(CalendarEvent $event): void {
    $colors = $this->options['colors']['calendar_colors_type'] ?? [];
    if (empty($colors)) {
      return;
    }

    $type_names = array_map(function ($bundle_info) {
      return $bundle_info['label'];
    }, $this->bundleInfo->getBundleInfo('node'));
    $bundle = $event->getBundle();
    $label = '';
    $stripeHex = '';
    if (array_key_exists($bundle, $type_names) || $colors[$bundle] == static::CALENDAR_EMPTY_STRIPE) {
      $label = $type_names[$bundle];
    }
    if (array_key_exists($bundle, $colors)) {
      $stripeHex = $colors[$bundle];
    }

    $event->addStripeLabel($label);
    $event->addStripeHex($stripeHex);
  }

  /**
   * Create a stripe based on a taxonomy term.
   *
   * @param \Drupal\calendar\CalendarEvent $event
   *   A calendar event.
   */
  public function calendarTaxonomyStripe(CalendarEvent $event) {
    $colors = $this->options['colors']['calendar_colors_taxonomy'] ?? [];
    if (empty($colors)) {
      return;
    }

    $entity = $event->getEntity();
    $term_field_name = $this->options['colors']['taxonomy_field'];
    if ($entity->hasField($term_field_name) && $terms_for_entity = $entity->get($term_field_name)) {
      /** @var \Drupal\Core\Field\EntityReferenceFieldItemListInterface $item */
      foreach ($terms_for_entity as $item) {
        $tid = $item->getValue()['target_id'];
        $term = Term::load($tid);

        if (!array_key_exists($tid, $colors) || $colors[$tid] == static::CALENDAR_EMPTY_STRIPE) {
          continue;
        }
        $event->addStripeLabel($term->label());
        $event->addStripeHex($colors[$tid]);
      }
    }
  }

  /**
   * Get form options for hiding elements based on legend type.
   */
  protected function visibleOnLegendState(string $mode): array {
    return [
      '#states' => [
        'visible' => [
          ':input[name="row_options[colors][legend]"]' => ['value' => $mode],
        ],
      ],
    ];
  }

}
