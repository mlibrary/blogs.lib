<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\taxonomy\Plugin\migrate\source\d7\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns bare-bones information about every available file entity.
 *
 * @MigrateSource(
 *   id = "media_entity_generator_d7_taxonomy",
 *   source_module = "file",
 * )
 */
class MediaEntityGeneratorTaxonomyD7 extends Term implements ContainerFactoryPluginInterface {

  /**
   * @var array
   */
  protected $sourceFields = [];

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $entityQuery;

  /**
   * MediaEntityGenerator constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Database\Connection $connection
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    StateInterface $state,
    EntityManagerInterface $entity_manager,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager, $module_handler);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;

    // Do not Join tables.
    $this->configuration['ignore_map'] = TRUE;

    foreach ($this->configuration['field_names'] as $name) {
      $this->sourceFields[$name] = $name;
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration = NULL
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('module_handler')
    );
  }

  /**
   *
   */
  public function count($refresh = FALSE) {
    return $this->initializeIterator()->count();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'target_id' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {

    $query_files = $this->select('file_managed', 'f')
      ->fields('f')
      ->condition('uri', 'temporary://%', 'NOT LIKE')
      ->orderBy('f.timestamp');

    $all_files = $query_files->execute()->fetchAllAssoc('fid');

    $files_found = [];

    foreach ($this->sourceFields as $name => $source_field) {

      $parent_iterator = parent::initializeIterator();

      foreach ($parent_iterator as $entity) {
        $id = $entity['tid'];
        $field_value = $this->getFieldValues($this->configuration['entity_type'], $name, $id);

        foreach ($field_value as $reference) {

          // Support remote file urls.
          $file_url = $all_files[$reference['fid']]['uri'];
          if (!empty($this->configuration['d7_file_url'])) {
            $file_url = str_replace('public://', '', $file_url);
            $file_path = rawurlencode($file_url);
            $file_url = $this->configuration['d7_file_url'] . $file_path;
          }

          if (!empty($all_files[$reference['fid']]['uri'])) {

            $files_found[] = [
              'tid' => $entity['tid'],
              'target_id' => $reference['fid'],
              'alt' => isset($reference['alt']) ? $reference['alt'] : NULL,
              'title' => isset($reference['title']) ? $reference['title'] : NULL,
              'display' => isset($reference['display']) ? $reference['display'] : NULL,
              'description' => isset($reference['description']) ? $reference['description'] : NULL,
              'langcode' => $this->configuration['langcode'],
              'entity' => $entity,
              'file_name' => $all_files[$reference['fid']]['filename'],
              'file_path' => $file_url,
            ];
          }
        }
      }
    }
    return new \ArrayIterator($files_found);
  }

}
