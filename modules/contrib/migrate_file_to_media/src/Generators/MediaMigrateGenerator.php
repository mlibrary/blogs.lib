<?php

namespace Drupal\migrate_file_to_media\Generators;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Database;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Automatically generates yml files for migrations.
 */
class MediaMigrateGenerator extends BaseGenerator {

  protected $name = 'd8:yml:migrate_file_to_media_migration_media';

  protected $alias = 'mf2m_media';

  protected $description = 'Generates yml for File to Media Migration';

  protected $templatePath = __DIR__;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {

    /** @var \Symfony\Component\Console\Question\Question[] $questions */
    $questions = Utils::defaultPluginQuestions() + [
      'migration_group' => new Question('Migration Group', 'media'),
      'entity_type' => new Question('Entity Type', 'node'),
      'source_bundle' => new Question('Source Bundle', ''),
      'source_field_name' => new Question('Source Field Names (comma separated)', 'field_image'),
      'target_bundle' => new Question('Target Media Type', 'image'),
      'target_field' => new Question('Target Field', 'field_media_image'),
      'lang_code' => new Question('Language Code', 'en'),
      'translation_languages' => new Question('Translation languages (comma separated)', 'none'),
    ];

    $questions['plugin_id']->setValidator([MediaMigrateGenerator::class, 'validatePluginId']);

    $vars = &$this->collectVars($input, $output, $questions);

    $vars['translation_language'] = NULL;

    if ($vars['translation_languages']) {
      $translation_languages = array_map('trim', array_unique(explode(',', strtolower($vars['translation_languages']))));
      // Validate the default language was not included in the translation languages
      foreach ($translation_languages as $key => $language) {
        if ($language == $vars['lang_code']) {
          unset($translation_languages[$key]);
        }
      }
      $vars['translation_languages'] = $translation_languages;
    }

    if ($vars['source_field_name']) {
      $vars['source_field_name'] = array_map('trim', explode(',', strtolower($vars['source_field_name'])));
    }

    // ID Key for the entity type (nid for node, id for paragraphs).
    $entityType = $this->entityTypeManager->getDefinition($vars['entity_type']);
    $vars['id_key'] = $entityType->getKey('id');

    $this->addFile()
      ->path('config/install/migrate_plus.migration.{plugin_id}_step1.yml')
      ->template('media-migration-step1.yml.twig')
      ->vars($vars);

    // Validates if there are translation languages and includes a new variable to add translations or not
    $vars['has_translation'] = (count($vars['translation_languages']) > 0 && $vars['translation_languages'][0] != 'none');
    $this->addFile()
      ->path('config/install/migrate_plus.migration.{plugin_id}_step2.yml')
      ->template('media-migration-step2.yml.twig')
      ->vars($vars);

    foreach ($vars['translation_languages'] as $language) {
      if ($language == 'none' || $language == $vars['lang_code']) {
        continue;
      }
      $vars['source_lang_code'] = $vars['lang_code'];
      $vars['translation_language'] = $vars['lang_code'] = $language;

      $this->addFile()
        ->path("config/install/migrate_plus.migration.{plugin_id}_step1_{$language}.yml")
        ->template('media-migration-step1.yml.twig')
        ->vars($vars);
    }

  }

  /**
   * Plugin id validator.
   */
  public static function validatePluginId($value) {
    // Check the length of the global table name prefix.
    $db_info = array_shift(Database::getConnectionInfo());
    $db_info = Database::parseConnectionInfo($db_info);
    $max_length = 48 - strlen($db_info['prefix']['default']);

    // Check if the plugin machine name is valid.
    Utils::validateMachineName($value);

    // Check the maximum number of characters for the migration name. The name
    // should not exceed 48 characters to prevent mysql table name limitation of
    // 64 characters for the table: migrate_message_[PLUGIN_ID].
    if (strlen($value) > $max_length) {
      throw new \UnexpectedValueException('The plugin id should not exceed more than ' . strval($max_length) . ' characters.');
    }
    return $value;
  }

}
