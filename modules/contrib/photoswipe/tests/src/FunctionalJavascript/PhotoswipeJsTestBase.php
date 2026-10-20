<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the photoswipe display setting on an image file.
 *
 * @group photoswipe
 */
abstract class PhotoswipeJsTestBase extends WebDriverTestBase {

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'test_page_test',
    'file',
    'image',
    'node',
    'photoswipe',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->publicFilesDirectory = 'public://';
    $this->config('system.site')->set('page.front', '/test-page')->save();

    $this->user = $this->createUser([]);
    $this->adminUser = $this->createUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
    $this->config('photoswipe.settings')->set('enable_cdn', TRUE)->save();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Validates if cdn libraries are loaded properly.
   */
  protected function validateCdnLibraries($session): void {
    $session->elementExists('css', 'link[href*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.4/photoswipe.min.css"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.4/umd/photoswipe-lightbox.umd.min.js"]');
    $session->elementExists('css', 'script[src*="//cdnjs.cloudflare.com/ajax/libs/photoswipe/5.4.4/umd/photoswipe.umd.min.js"]');
  }

  /**
   * Create a new image field.
   *
   * Create a new image field.
   * Modified Version of Drupal\Tests\image\Kernel\ImageFieldCreationTrait
   * "createImageField" function.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type_id
   *   The entity type that this field will be added to.
   * @param string $bundle_id
   *   The entity type bundle that this field will be added to.
   * @param array $storage_settings
   *   (optional) A list of field storage settings that will be added to the
   *   defaults.
   * @param array $field_settings
   *   (optional) A list of instance settings that will be added to the instance
   *   defaults.
   * @param array $widget_settings
   *   (optional) Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   (optional) Formatter settings to be added to the formatter defaults.
   * @param string $formatter_type
   *   (optional) The formatter type, defaults to 'photoswipe_field_formatter'.
   * @param string $description
   *   (optional) A description for the field. Defaults to ''.
   * @param array $thirdPartyFormatterSettings
   *   (optional) The view display third party settings.
   *   Array convention:
   *   [
   *     'module_id' => [
   *        'setting_key' => 'setting_value',
   *      ],
   *   ].
   */
  protected function createImageField($name, $entity_type_id = 'node', $bundle_id = 'article', array $storage_settings = [], array $field_settings = [], array $widget_settings = [], array $formatter_settings = [], $formatter_type = 'photoswipe_field_formatter', $description = '', $thirdPartyFormatterSettings = []) {
    FieldStorageConfig::create([
      'field_name' => $name,
      'entity_type' => $entity_type_id,
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => !empty($storage_settings['cardinality']) ? $storage_settings['cardinality'] : 1,
    ])->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type_id,
      'bundle' => $bundle_id,
      'required' => !empty($field_settings['required']),
      'settings' => $field_settings,
      'description' => $description,
    ]);
    $field_config->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository->getFormDisplay($entity_type_id, $bundle_id)
      ->setComponent($name, [
        'type' => 'image_image',
        'settings' => $widget_settings,
      ])
      ->save();

    $display_repository->getViewDisplay($entity_type_id, $bundle_id)
      ->setComponent($name, [
        'type' => $formatter_type,
        'settings' => $formatter_settings,
        'third_party_settings' => $thirdPartyFormatterSettings,
      ])
      ->save();

    return $field_config;
  }

}
