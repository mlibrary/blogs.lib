<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\FileInterface;
use Drupal\file_entity\Entity\FileType;

/**
 * Tests the file entity types.
 *
 * @group file_entity
 */
class FileEntityTypeTest extends FileEntityTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->setUpFiles();
  }

  /**
   * Test admin pages access and functionality.
   */
  public function testAdminPages() {
    // Create a user with file type administration access.
    $user = $this->drupalCreateUser(array('administer file types'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/file-types');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test creating a new type. Basic CRUD.
   */
  public function testCreate() {
    $type_machine_type = 'foo';
    $type_machine_label = 'foobar';
    $this->createFileType(array('id' => $type_machine_type, 'label' => $type_machine_label));
    $loaded_type = FileType::load($type_machine_type);
    $this->assertEquals($loaded_type->label(), $type_machine_label, "Was able to create a type and retreive it.");
  }

  /**
   * Make sure candidates are presented in the case of multiple file types.
   */
  public function testTypeWithCandidates() {
    // Create multiple file types with the same mime types.
    array(
      'image1' => $this->createFileType(array('id' => 'image1', 'label' => 'Image 1')),
      'image2' => $this->createFileType(array('id' => 'image2', 'label' => 'Image 2')),
    );

    // Attach a text field to one of the file types.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'type' => 'string',
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'file',
      'bundle' => 'image2',
    ));
    $field_instance->save();
    \Drupal::service('entity_display.repository')->getFormDisplay('file', 'image2')
      ->setComponent($field_name, array(
        'type' => 'text_textfield',
      ))
      ->save();


    // Create a user with file creation access.
    $user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($user);

    // Step 1: Upload file.
    $file = reset($this->files['image']);
    $edit = array();
    $edit['files[upload]'] = \Drupal::service('file_system')->realpath($file->getFileUri());
    $this->drupalGet('file/add');
    $this->submitForm($edit, t('Next'));

    // Step 2: Select file type candidate.
    $this->assertSession()->pageTextContains('Image 1');
    $this->assertSession()->pageTextContains('Image 2');
    $edit = array();
    $edit['type'] = 'image2';
    $this->submitForm($edit, t('Next'));

    // Step 3: Select file scheme candidate.
    $this->assertSession()->pageTextContains('Public local files served by the webserver.');
    $this->assertSession()->pageTextContains('Private local files served by Drupal.');
    $edit = array();
    $edit['scheme'] = 'public';
    $this->submitForm($edit, t('Next'));

    // Step 4: Complete field widgets.
    $edit = array();
    $edit["{$field_name}[0][value]"] = $this->randomMachineName();
    $edit['filename[0][value]'] = $this->randomMachineName();
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->responseContains(t('@type %name was uploaded.', array('@type' => 'Image 2', '%name' => $edit['filename[0][value]'])));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($edit['filename[0][value]']);
    $this->assertInstanceOf(FileInterface::class, $file, t('File found in database.'));

    // Checks if configurable field exists in the database.
    $this->assertTrue($file->hasField($field_name), 'Found configurable field in database');
  }

  /**
   * Make sure no candidates appear when only one mime type is available.
   */
  public function testTypeWithoutCandidates() {
    // Attach a text field to the default image file type.
    $field_name = mb_strtolower($this->randomMachineName());
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'file',
      'type' => 'string',
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'file',
      'bundle' => 'image',
    ));
    $field_instance->save();
    \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('file.image.default')
      ->setComponent($field_name, array(
      'type' => 'text_textfield',
      ))
      ->save();

    // Create a user with file creation access.
    $user = $this->drupalCreateUser(array('create files'));
    $this->drupalLogin($user);

    // Step 1: Upload file.
    $file = reset($this->files['image']);
    $edit = array();
    $edit['files[upload]'] = \Drupal::service('file_system')->realpath($file->getFileUri());
    $this->drupalGet('file/add');
    $this->submitForm($edit, t('Next'));

    // Step 2: Scheme selection.
    if ($this->xpath('//input[@name="scheme"]')) {
      $this->submitForm(array(), t('Next'));
    }

    // Step 3: Complete field widgets.
    $edit = array();
    $edit["{$field_name}[0][value]"] = $this->randomMachineName();
    $edit['filename[0][value]'] = $this->randomMachineName();
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->responseContains(t('@type %name was uploaded.', array('@type' => 'Image', '%name' => $edit['filename[0][value]'])));

    // Check that the file exists in the database.
    $file = $this->getFileByFilename($edit['filename[0][value]']);
    $this->assertInstanceOf(FileInterface::class, $file, t('File found in database.'));

    // Checks if configurable field exists in the database.
    $this->assertTrue($file->hasField($field_name), 'Found configurable field in database');
  }

  /**
   * Test file types CRUD UI.
   */
  public function testTypesCrudUi() {
    $this->drupalGet('admin/structure/file-types');
    $this->assertSession()->statusCodeEquals(403);

    $user = $this->drupalCreateUser(array('administer file types'));
    $this->drupalLogin($user);

    $this->drupalGet('admin/structure/file-types');
    $this->assertSession()->statusCodeEquals(200);

    // Create new file type.
    $edit = array(
      'label' => t('Test type'),
      'id' => 'test_type',
      'description' => t('This is dummy file type used just for testing.'),
      'mimetypes' => 'image/png',
    );
    $this->drupalGet('admin/structure/file-types/add');
    $this->submitForm($edit, t('Save'));
    $this->assertSession()->pageTextContains(t('The file type @type has been added.', array('@type' => $edit['label'])));
    $this->assertSession()->pageTextContains($edit['label']);
    $this->assertSession()->pageTextContains($edit['description']);
    $this->assertSession()->linkExists(t('Disable'));
    $this->assertSession()->linkExists(t('Delete'));
    $this->assertSession()->linkByHrefExists('admin/structure/file-types/manage/' . $edit['id'] . '/disable');
    $this->assertSession()->linkByHrefExists('admin/structure/file-types/manage/' . $edit['id'] . '/delete');

    // Edit file type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/edit');
    $this->assertSession()->responseContains(t('Save'));
    $this->assertSession()->responseContains(t('Delete'));
    $this->assertSession()->responseContains($edit['label']);
    $this->assertSession()->pageTextContains($edit['description']);
    $this->assertSession()->pageTextContains($edit['mimetypes']);
    $this->assertSession()->pageTextContains(t('Known MIME types'));

    // Modify file type.
    $edit['label'] = t('New type label');
    $this->submitForm(array('label' => $edit['label']), t('Save'));
    $this->assertSession()->pageTextContains(t('The file type @type has been updated.', array('@type' => $edit['label'])));
    $this->assertSession()->pageTextContains($edit['label']);

    // Disable and re-enable file type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/disable');
    $this->assertSession()->pageTextContains(t('Are you sure you want to disable the file type @type?', array('@type' => $edit['label'])));
    $this->submitForm(array(), t('Disable'));
    $this->assertSession()->pageTextContains(t('The file type @type has been disabled.', array('@type' => $edit['label'])));
    $this->assertSession()->elementContains('css', 'tbody tr:nth-child(5) td:nth-child(1)', $edit['label']);
    $this->assertSession()->linkExists(t('Enable'));
    $this->assertSession()->linkByHrefExists('admin/structure/file-types/manage/' . $edit['id'] . '/enable');
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/enable');
    $this->assertSession()->pageTextContains(t('Are you sure you want to enable the file type @type?', array('@type' => $edit['label'])));
    $this->submitForm(array(), t('Enable'));
    $this->assertSession()->pageTextContains(t('The file type @type has been enabled.', array('@type' => $edit['label'])));
    $this->assertSession()->elementContains('css', 'tbody tr:nth-child(4) td:nth-child(1)', $edit['label']);

    // Delete newly created type.
    $this->drupalGet('admin/structure/file-types/manage/' . $edit['id'] . '/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the file type @type?', array('@type' => $edit['label'])));
    $this->submitForm(array(), t('Delete'));
    $this->assertSession()->pageTextContains(t('The file type @type has been deleted.', array('@type' => $edit['label'])));
    $this->drupalGet('admin/structure/file-types');
    $this->assertSession()->pageTextNotContains($edit['label']);

    // Edit pre-defined file type.
    $this->drupalGet('admin/structure/file-types/manage/image/edit');
    $this->assertSession()->responseContains(t('Image'));
    $this->assertSession()->pageTextContains("image/*");
    $this->submitForm(array('label' => t('Funky images')), t('Save'));
    $this->assertSession()->pageTextContains(t('The file type @type has been updated.', array('@type' => t('Funky images'))));
    $this->assertSession()->pageTextContains(t('Funky image'));
  }
}
