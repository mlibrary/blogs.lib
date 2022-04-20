<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\source;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\node\Plugin\migrate\source\d7\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns bare-bones information about every available file entity.
 *
 * @MigrateSource(
 *   id = "media_entity_generator_d7",
 *   source_module = "file",
 * )
 */
class MediaEntityGeneratorD7 extends Node implements ContainerFactoryPluginInterface {

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
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  private $entityQuery;

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';

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
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager, $module_handler);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->connection = $connection;

    // Do not joint source tables.
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
      $container->get('state'),
      $container->get('module_handler')
    );
  }

  public function count($refresh = FALSE) {
    return $this->initializeIterator()->count();
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
      'file_mime' => $this->t('The file mime type'),
      'file_type' => $this->t('The file type'),
    ];
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
  public function query() {

    // Select node in its last revision.
    $query = $this->select('node_revision', 'nr')
      ->fields(
        'n',
        [
          'nid',
          'type',
          'language',
          'status',
          'created',
          'changed',
          'comment',
          'promote',
          'sticky',
          'tnid',
          'translate',
        ]
      )
      ->fields(
        'nr',
        [
          'vid',
          'title',
          'log',
          'timestamp',
        ]
      );
    $query->addField('n', 'uid', 'node_uid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);

    // If the content_translation module is enabled, get the source langcode
    // to fill the content_translation_source field.
    if ($this->moduleHandler->moduleExists('content_translation')) {
      $query->leftJoin('node', 'nt', 'n.tnid = nt.nid');
      $query->addField('nt', 'language', 'source_langcode');
    }
    $this->handleTranslations($query);

    if (isset($this->configuration['bundle'])) {
      $query->condition('n.type', $this->configuration['bundle'], is_array($this->configuration['bundle']) ? 'IN' : '=');
    }

    return $query;
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
        $nid = $entity['nid'];
        $vid = $entity['vid'];
        $langcode = !empty($this->configuration['langcode']) ? $this->configuration['langcode'] : NULL;
        $field_value = $this->getFieldValues('node', $name, $nid, $vid, $langcode);

        foreach ($field_value as $reference) {

          if (!empty($all_files[$reference['fid']]['uri'])) {

            // Support remote file urls.
            $file_url = $all_files[$reference['fid']]['uri'];
            if (!empty($this->configuration['d7_file_url'])) {
              $file_url = str_replace('public://', '', $file_url);
              $file_path = UrlHelper::encodePath($file_url);
              $file_url = $this->configuration['d7_file_url'] . $file_path;
            }

            // Make sure the file name is correct based on the file url.
            $file_name = $all_files[$reference['fid']]['filename'];
            $file_url_pieces = explode('/', $file_url);
            if ($file_name !== end($file_url_pieces)) {
              $file_name = end($file_url_pieces);
            }

            $files_found[] = [
              'nid' => $entity['nid'],
              'target_id' => $reference['fid'],
              'alt' => isset($reference['alt']) ? $reference['alt'] : NULL,
              'title' => isset($reference['title']) ? $reference['title'] : NULL,
              'display' => isset($reference['display']) ? $reference['display'] : NULL,
              'description' => isset($reference['description']) ? $reference['description'] : NULL,
              'langcode' => $this->configuration['langcode'],
              'entity' => $entity,
              'file_name' => $file_name,
              'file_path' => $file_url,
              'file_mime' => $all_files[$reference['fid']]['filemime'],
              'file_type' => $all_files[$reference['fid']]['type'],
            ];
          }
        }
      }
    }
    return new \ArrayIterator($files_found);
  }

}
