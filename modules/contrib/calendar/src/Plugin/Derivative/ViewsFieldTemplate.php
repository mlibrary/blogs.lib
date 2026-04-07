<?php

namespace Drupal\calendar\Plugin\Derivative;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\views\ViewsData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivative class to find all field and properties for calendar View Builders.
 */
class ViewsFieldTemplate implements ContainerDeriverInterface {

  /**
   * List of derivative definitions.
   *
   * @var string[]
   */
  protected array $derivatives = [];

  /**
   * Constructs a ViewsBlock object.
   */
  public function __construct(
    protected string $basePluginId,
    protected EntityTypeManagerInterface $entityManager,
    protected ViewsData $viewsData,
    protected EntityFieldManagerInterface $fieldManager,
    protected FieldTypePluginManagerInterface $fieldTypeManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('views.views_data'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinition($derivative_id, $base_plugin_definition) {
    if (!empty($this->derivatives) && !empty($this->derivatives[$derivative_id])) {
      return $this->derivatives[$derivative_id];
    }
    $this->getDerivativeDefinitions($base_plugin_definition);
    return $this->derivatives[$derivative_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Just add support for entity types which have a views integration.
      if (($base_table = $entity_type->getBaseTable()) && $this->viewsData->get($base_table) && $this->entityManager->hasHandler($entity_type_id, 'view_builder')) {
        $entity_views_tables = [$base_table => $this->viewsData->get($base_table)];
        if ($data_table = $entity_type->getDataTable()) {
          $entity_views_tables[$data_table] = $this->viewsData->get($data_table);
        }
        foreach ($entity_views_tables as $table_id => $entity_views_table) {
          foreach ($entity_views_table as $field_info) {
            if ($this->isDateField($field_info)) {
              $derivative = [
                'replacements' => [
                  'entity_label' => $entity_type->getLabel(),
                  'entity_type' => $entity_type_id,
                  'field_id' => $field_info['entity field'],
                  'base_table' => $table_id,
                  'base_field' => $this->getTableBaseField($entity_views_table),
                  'default_field_id' => $this->getTableDefaultField($entity_views_table, $entity_type_id),
                  'field_label' => $field_info['title'],
                ],
                'view_template_id' => 'calendar_base_field',
              ];
              $this->setDerivative($derivative, $base_plugin_definition);
            }
          }
        }
        $this->setConfigurableFieldsDerivatives($entity_type, $base_plugin_definition);
      }

    }
    return $this->derivatives;
  }

  /**
   * Set all derivatives for an entity type.
   */
  protected function setConfigurableFieldsDerivatives(EntityTypeInterface $entity_type, array $base_plugin_definition): void {
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface[] $field_storages */
    $field_storages = $this->fieldManager->getFieldStorageDefinitions($entity_type->id());

    foreach ($field_storages as $field_id => $field_storage) {
      $type = $field_storage->getType();
      $field_definition = $this->fieldTypeManager->getDefinition($type);
      $class = $field_definition['class'];
      $classes = [];
      $classes[$type] = [];
      $classes[$type][] = $class;
      while ($class !== FALSE) {
        $classes[$type][] = get_parent_class($class);
        $class = end($classes[$type]);
      }
      if (in_array("Drupal\datetime\Plugin\Field\FieldType\DateTimeItem", $classes[$type])) {
        $entity_type_id = $entity_type->id();
        $views_data = $this->viewsData->getAll();
        $field_table = NULL;
        foreach ($views_data as $key => $data) {
          if (strstr($key, $field_id) && isset($data[$field_id])) {
            $field_table = $key;
            $field_table_data = $data;
            break;
          }
        }
        if (isset($field_table_data)) {
          $field_info = $field_table_data[$field_id];
          $join_tables = array_keys($field_table_data['table']['join']);
          $join_table = array_pop($join_tables);
          $join_table_data = $this->viewsData->get($join_table);
          $derivative = [
            'replacements' => [
              'field_id' => $field_id,
              'entity_type' => $entity_type_id,
              'entity_label' => $entity_type->getLabel(),
              'field_label' => $field_info['title'],
              'base_table' => $join_table,
              'field_table' => $field_table,
              'default_field_id' => $this->getTableDefaultField($join_table_data, $entity_type_id),
              'base_field' => $this->getTableBaseField($join_table_data),
            ],
            'view_template_id' => 'calendar_config_field',
          ];
          $this->setDerivative($derivative, $base_plugin_definition);
        }

      }

    }
  }

  /**
   * Determine if a field is an date field.
   */
  protected function isDateField(array $field_info): bool {
    if (!empty($field_info['field']['id']) && $field_info['field']['id'] == 'field') {
      if (!empty($field_info['argument']['id']) && $field_info['argument']['id'] == 'date') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function setDerivative(array $derivative, array $base_plugin_definition) {

    $info = $derivative['replacements'];

    $derivative_id = $info['entity_type'] . '__' . $info['field_id'];
    // Move some replacements values to root of derivative also.
    $derivative['entity_type'] = $info['entity_type'];
    $derivative['field_id'] = $info['field_id'];
    // Create base path.
    if ($derivative['entity_type'] == 'node') {
      $base_path = 'calendar-' . $derivative['field_id'];
    }
    else {
      $base_path = "calendar-{$derivative['entity_type']}-{$derivative['field_id']}";
    }
    $derivative['replacements']['base_path'] = $base_path;
    $derivative['id'] = $base_plugin_definition['id'] . ':' . $derivative_id;
    $derivative += $base_plugin_definition;

    $this->derivatives[$derivative_id] = $derivative;
  }

  /**
   * Return the default field from a View table array.
   */
  private function getTableDefaultField(array $table_data, ?string $entity_type_id = NULL): ?string {
    $default_field_id = NULL;
    if (!empty($table_data['table']['base']['defaults']['field'])) {
      $default_field_id = $table_data['table']['base']['defaults']['field'];
    }
    if (empty($default_field_id)) {
      if ($entity_type_id == 'user') {
        $default_field_id = 'name';
      }
    }

    return $default_field_id;
  }

  /**
   * Return the base field ID from a View table data array.
   */
  private function getTableBaseField(array $table_data): ?string {
    if (!empty($table_data['table']['base']['field'])) {
      return $table_data['table']['base']['field'];
    }

    return NULL;
  }

}
