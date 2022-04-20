<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\source;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Entity\File;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns bare-bones information about every available file entity.
 *
 * @MigrateSource(
 *   id = "media_entity_generator",
 *   source_module = "file",
 * )
 */
class MediaEntityGenerator extends SourcePluginBase implements ContainerFactoryPluginInterface {

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
    Connection $connection
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;

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
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Set source file.
    if (!empty($row->getSource()['target_id'])) {
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($row->getSource()['target_id']);
      if ($file) {
        $row->setSourceProperty('file_path', $file->getFileUri());
        $row->setSourceProperty('file_name', $file->getFilename());
        $row->setSourceProperty('uid', $file->getOwnerId());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'target_id' => $this->t('The file entity ID.'),
      'file_id' => $this->t('The file entity ID.'),
      'file_path' => $this->t('The file path.'),
      'file_name' => $this->t('The file name.'),
      'file_alt' => $this->t('The file arl.'),
      'file_title' => $this->t('The file title.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return '';
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

    $entityDefinition = $this->entityTypeManager->getDefinition($this->configuration['entity_type']);
    $bundleKey = $entityDefinition->getKey('bundle');

    $files_found = [];

    foreach ($this->sourceFields as $name => $source_field) {

      $query = $this->entityTypeManager->getStorage($this->configuration['entity_type'])->getQuery();

      if (!empty($bundleKey)) {
        $query->condition($bundleKey, $this->configuration['bundle'], is_array($this->configuration['bundle']) ? 'IN' : '=');
      }

      $query->condition("{$name}.target_id", 0, '>', $this->configuration['langcode']);

      // Also migrate unpublished and restricted entities.
      $query->accessCheck(FALSE);
      $results = $query->execute();

      if ($results) {

        $entitites = $this->entityTypeManager->getStorage($this->configuration['entity_type'])
          ->loadMultiple($results);

        foreach ($entitites as $entity) {
          $original_entity = NULL;
          if ($entity->hasTranslation($this->configuration['langcode'])) {
            $original_entity = $entity->createDuplicate();
            $entity = $entity->getTranslation($this->configuration['langcode']);
          }

          foreach ($entity->{$name}->getValue() as $reference) {
            $data = [
              'nid' => $entity->id(),
              'target_id' => $reference['target_id'],
              'alt' => isset($reference['alt']) ? $reference['alt'] : NULL,
              'title' => isset($reference['title']) ? $reference['title'] : NULL,
              'display' => isset($reference['display']) ? $reference['display'] : NULL,
              'description' => isset($reference['description']) ? $reference['description'] : NULL,
              'langcode' => $this->configuration['langcode'],
              'entity' => $entity,
            ];

            if ($original_entity) {
              $original_ref = $original_entity->{$name}->getValue()[0];
              $data['source_language_target_id'] = $original_ref['target_id'];
            }

            $files_found[] = $data;

          }
        }
      }
    }
    return new \ArrayIterator($files_found);
  }

}
