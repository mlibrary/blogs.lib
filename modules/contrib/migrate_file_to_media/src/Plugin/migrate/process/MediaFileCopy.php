<?php

namespace Drupal\migrate_file_to_media\Plugin\migrate\process;

use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\crop\Entity\Crop;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\FileCopy;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copies or local file for usage in media module.
 *
 * Examples:
 *
 * @code
 * process:
 *   path_to_file:
 *     plugin: file_copy
 *     source:
 *       - id
 *       - public://new/path/to/
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "media_file_copy"
 * )
 */
class MediaFileCopy extends FileCopy implements ContainerFactoryPluginInterface {
  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system, MigrateProcessInterface $download_plugin, ModuleHandler $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $stream_wrappers, $file_system, $download_plugin);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('stream_wrapper_manager'),
      $container->get('file_system'),
      $container->get('plugin.manager.migrate.process')->createInstance('download', $configuration),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($source_id, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // If we're stubbing a file entity, return a URI of NULL so it will get
    // stubbed by the general process.
    if ($row->isStub()) {
      return NULL;
    }

    $destination_folder = $this->configuration['path'] ?? 'public://media/';

    $destination = $destination_folder . $row->getSourceProperty('file_name');

    $source_file = false;

    // If the source path or URI represents a remote resource, delegate to the
    // download plugin.
    if (!$this->isLocalUri($source_id)) {
      $source = $this->downloadPlugin->transform([
        $source_id,
        'public://download/' . $row->getSourceProperty('file_name'),
      ], $migrate_executable, $row, $destination_property);
    }
    else {
      $source_file = File::load($source_id);
      $source = $source_file->getFileUri();
    }

    if ($source_file) {
      $destination = $destination_folder . $source_file->getFilename();
    }

    // Ensure the source file exists, if it's a local URI or path.
    if (!file_exists($source)) {
      throw new MigrateException("File '$source' does not exist");
    }

    // Prepare destination folder.
    if (strpos($destination_folder, 'rokka') !== 0) {
      // Check if a writable directory exists, and if not try to create it.
      $dir = $this->getDirectory($destination);
      // If the directory exists and is writable, avoid file_prepare_directory()
      // call and write the file to destination.
      if (!is_dir($dir) || !is_writable($dir)) {
        if (!\Drupal::service('file_system')->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
          throw new MigrateException("Could not create or write to directory '$dir'");
        }
      }
    }

    $final_destination = $this->saveFile($source, $destination);
    if ($final_destination) {
      if ($this->moduleHandler->moduleExists('crop')) {
        $this->updateFocalPoint($source, $final_destination->getFileUri(), $final_destination);
      }
      return $final_destination->id();
    }

    throw new MigrateException("File $source could not be copied to $destination");
  }

  /**
   * Save file to a defined destination.
   */
  protected function saveFile($source, $destination, $replace = FileSystemInterface::EXISTS_RENAME) {
    $data = file_get_contents($source);
    $file = file_save_data($data, $destination, $replace);
    return $file;
  }

  /**
   * Update focal point.
   *
   * @param $uri_old
   *   Old URI.
   * @param $uri_rokka
   *   Rokka URI.
   * @param $rokka_file
   *   Rokka file.
   */
  private function updateFocalPoint($uri_old, $uri_rokka, $rokka_file) {

    try {

      /** @var \Drupal\crop\Entity\Crop $old_crop */
      $old_crop = Crop::findCrop($uri_old, 'focal_point');

      if (!empty($old_crop)) {

        $crop = Crop::create([
          'type' => 'focal_point',
          'entity_id' => $rokka_file->id(),
          'entity_type' => 'file',
          'uri' => $uri_rokka,
          'height' => $old_crop->height->value,
          'width' => $old_crop->width->value,
          'x' => $old_crop->x->value,
          'y' => $old_crop->y->value,
        ]);

        $crop->save();
      }

    }
    catch (\Exception $exception) {
      throw new MigrateException('Failed to save the focal point to rokka');
    }

  }

}
