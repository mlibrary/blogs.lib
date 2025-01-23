<?php

namespace Drupal\Tests\file_entity\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;

/**
 * Test file administration page functionality.
 *
 * @group file_entity
 */
class FileEntityAdminTest extends FileEntityTestBase {

  /** @var User */
  protected $userAdmin;

  /** @var User */
  protected $userBasic;

  /** @var User */
  protected $userViewOwn;

  /** @var User */
  protected $userViewPrivate;

  /** @var User */
  protected $userEditDelete;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    // Add the tasks and actions blocks.
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    // Remove the "view files" permission which is set
    // by default for all users so we can test this permission
    // correctly.
    $roles = Role::loadMultiple();
    foreach ($roles as $rid => $role) {
      user_role_revoke_permissions($rid, array('view files'));
    }

    $this->userAdmin = $this->drupalCreateUser(array('administer files', 'bypass file access'));
    $this->userBasic = $this->drupalCreateUser(array('administer files'));
    $this->userViewOwn = $this->drupalCreateUser(array('administer files', 'view own private files'));
    $this->userViewPrivate = $this->drupalCreateUser(array('administer files', 'view private files'));
    $this->userEditDelete = $this->drupalCreateUser(array(
      'administer files',
      'edit any document files',
      'delete any document files',
      'edit any image files',
      'delete any image files',
    ));

    // Enable the enhanced Files view.
    View::load('files')->disable()->save();
    View::load('file_entity_files')->enable()->save();
  }

  /**
   * Tests that the table sorting works on the files admin pages.
   */
  public function testFilesAdminSort() {
    $this->drupalLogin($this->userAdmin);
    $i = 0;
    foreach (array('dd', 'aa', 'DD', 'bb', 'cc', 'CC', 'AA', 'BB') as $prefix) {
      $this->createFileEntity(array('filename' => $prefix . $this->randomMachineName(6), 'created' => $i * 90000));
      $i++;
    }

    // Test that the default sort by file_managed.created DESC fires properly.
    $files_query = array();
    foreach (\Drupal::entityQuery('file')->sort('created', 'DESC')->accessCheck(FALSE)->execute() as $fid) {
      $files_query[] = FileEntity::load($fid)->label();
    }

    $this->drupalGet('admin/content/files');
    $xpath = '//form[@id="views-form-file-entity-files-overview"]/table[@class="cols-10 responsive-enabled"]/tbody//tr/td[contains(@class, "views-field-filename")]';
    $list = $this->xpath($xpath);
    $entries = [];
    foreach ($list as $entry) {
      $entries[] = trim((string) $entry->getText());
    }
    $this->assertEquals($files_query, $entries, 'Files are sorted in the view according to the default query.');

    // Compare the rendered HTML node list to a query for the files ordered by
    // filename to account for possible database-dependent sort order.
    $files_query = array();
    foreach (\Drupal::entityQuery('file')->sort('filename')->accessCheck(FALSE)->execute() as $fid) {
      $files_query[] = FileEntity::load($fid)->label();
    }

    $this->drupalGet('admin/content/files', array('query' => array('sort' => 'asc', 'order' => 'filename')));
    $list = $this->xpath($xpath);
    $entries = [];
    foreach ($list as $entry) {
      $entries[] = trim((string) $entry->getText());
    }
    $this->assertEquals($files_query, $entries, 'Files are sorted in the view the same as they are in the query.');
  }

  /**
   * Tests files overview with different user permissions.
   */
  public function testFilesAdminPages() {
    $this->drupalLogin($this->userAdmin);

    /** @var FileEntity[] $files */
    $files['public_image'] = $this->createFileEntity(array(
      'scheme' => 'public',
      'uid' => $this->userBasic->id(),
      'type' => 'image',
    ));
    $files['public_document'] = $this->createFileEntity(array(
      'scheme' => 'public',
      'uid' => $this->userViewOwn->id(),
      'type' => 'document',
    ));
    $files['private_image'] = $this->createFileEntity(array(
      'scheme' => 'private',
      'uid' => $this->userBasic->id(),
      'type' => 'image',
    ));
    $files['private_document'] = $this->createFileEntity(array(
      'scheme' => 'private',
      'uid' => $this->userViewOwn->id(),
      'type' => 'document',
    ));

    // Verify view, edit, and delete links for any file.
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);
    $i = 0;
    foreach ($files as $file) {
      $this->assertSession()->linkByHrefExists('file/' . $file->id());
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/edit');
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/delete');
      // Verify tableselect.
      $this->assertSession()->fieldExists("bulk_form[$i]");
    }

    // Verify no operation links beside download are displayed for regular
    // users.
    $this->drupalLogout();
    $this->drupalLogin($this->userBasic);
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('file/' . $files['public_image']->id());
    $this->assertSession()->linkByHrefExists('file/' . $files['public_document']->id());
    // Download access of public files is always allowed.
    $this->assertSession()->linkByHrefExists('file/' . $files['public_document']->id() . '/download');
    $this->assertSession()->linkByHrefExists('file/' . $files['public_document']->id() . '/download');
    $this->assertSession()->linkByHrefNotExists('file/' . $files['public_image']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('file/' . $files['public_image']->id() . '/delete');
    $this->assertSession()->linkByHrefNotExists('file/' . $files['public_document']->id() . '/edit');
    $this->assertSession()->linkByHrefNotExists('file/' . $files['public_document']->id() . '/delete');

    // Verify no tableselect.
    // @todo Drupal 8 always shows bulk selection, test specific actions
    //   instead.
    // $this->assertNoFieldByName('bulk_form[' . $files['public_image']->id() . ']', '', 'No bulk form checkbox found.');

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userViewOwn);
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists($files['private_document']->toUrl()->toString());
    // Verify no operation links are displayed.
    $this->drupalGet($files['private_document']->toUrl('edit-form'));
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($files['private_document']->toUrl('delete-form'));
    $this->assertSession()->statusCodeEquals(403);

    // Verify user cannot see private file of other users.
    $this->assertSession()->linkByHrefNotExists($files['private_image']->toUrl()->toString());
    $this->assertSession()->linkByHrefNotExists($files['private_image']->toUrl('edit-form')->toString());
    $this->assertSession()->linkByHrefNotExists($files['private_image']->toUrl('delete-form')->toString());
    $this->assertSession()->linkByHrefNotExists($files['private_image']->downloadUrl()->toString());

    // Verify no tableselect.
    $this->assertSession()->fieldNotExists('bulk_form[' . $files['private_document']->id() . ']');

    // Verify private file is displayed with permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userViewPrivate);
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);

    // Verify user can see private file of other users.
    $this->assertSession()->linkByHrefExists('file/' . $files['private_document']->id());
    $this->assertSession()->linkByHrefExists('file/' . $files['private_image']->id());

    // Verify operation links are displayed for users with appropriate
    // permission.
    $this->drupalLogout();
    $this->drupalLogin($this->userEditDelete);
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);
    foreach ($files as $file) {
      $this->assertSession()->linkByHrefExists('file/' . $file->id());
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/edit');
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/delete');
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/delete');
    }

    // Verify file access can be bypassed.
    $this->drupalLogout();
    $this->drupalLogin($this->userAdmin);
    $this->drupalGet('admin/content/files');
    $this->assertSession()->statusCodeEquals(200);
    foreach ($files as $file) {
      $this->assertSession()->linkByHrefExists('file/' . $file->id());
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/edit');
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/delete');
      $this->assertSession()->linkByHrefExists('file/' . $file->id() . '/download');
    }
  }

  /**
   * Tests single and bulk operations on the file overview.
   */
  public function testFileOverviewOperations() {
    $this->setUpFiles();
    $this->drupalLogin($this->userEditDelete);

    // Test single operations.
    $this->drupalGet('admin/content/files');
    $this->assertSession()->linkByHrefExists('file/1/delete');
    $this->assertSession()->linkByHrefExists('file/2/delete');
    $this->drupalGet('file/1/delete');
    $this->assertSession()->titleEquals((string) t('Are you sure you want to delete the file @filename? | Drupal', array('@filename' => FileEntity::load(1)->label())));
    $this->submitForm(array(), 'Delete');
    $this->assertSession()->linkByHrefNotExists('file/1/delete');
    $this->assertSession()->linkByHrefExists('file/2/delete');

    // Test bulk status change.
    // The "first" file now has id 2, but bulk form fields start counting at 0.
    $this->assertTrue(FileEntity::load(2)->isPermanent());
    $this->assertTrue(FileEntity::load(3)->isPermanent());
    $this->assertTrue(FileEntity::load(4)->isPermanent());
    $this->assertTrue(FileEntity::load(5)->isPermanent());

    $this->drupalGet('admin/content/files', array('query' => array('order' => 'fid')));
    $edit = array(
      'action' => 'file_temporary_action',
      'bulk_form[0]' => 1,
      'bulk_form[1]' => 1,
      'bulk_form[2]' => 1,
    );
    $this->submitForm($edit, 'Apply to selected items');

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    $this->assertFalse(FileEntity::load(2)->isPermanent());
    $this->assertFalse(FileEntity::load(3)->isPermanent());
    $this->assertFalse(FileEntity::load(4)->isPermanent());
    $this->assertTrue(FileEntity::load(5)->isPermanent());

    $this->drupalGet('admin/content/files', array('query' => array('order' => 'fid')));
    $edit = array(
      'action' => 'file_permanent_action',
      'bulk_form[0]' => 1,
      'bulk_form[1]' => 1,
    );
    $this->submitForm($edit, 'Apply to selected items');

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    $this->assertTrue(FileEntity::load(2)->isPermanent());
    $this->assertTrue(FileEntity::load(3)->isPermanent());
    $this->assertFalse(FileEntity::load(4)->isPermanent());
    $this->assertTrue(FileEntity::load(5)->isPermanent());

    // Test bulk delete.
    $this->drupalGet('admin/content/files', array('query' => array('order' => 'fid')));
    $edit = array(
      'action' => 'file_delete_action',
      'bulk_form[0]' => 1,
      'bulk_form[1]' => 1,
    );
    $this->submitForm($edit, 'Apply to selected items');
    $this->assertSession()->titleEquals('Are you sure you want to delete these files? | Drupal');
    $this->assertSession()->linkExists('Cancel');
    $this->submitForm(array(), 'Delete');

    \Drupal::entityTypeManager()->getStorage('file')->resetCache();
    $this->assertNull(FileEntity::load(2), 'File 2 is deleted.');
    $this->assertNull(FileEntity::load(3), 'File 3 is deleted.');
    $this->assertNotNull(FileEntity::load(4), 'File 4 is not deleted.');
  }

  /**
   * Tests the file usage view.
   */
  public function testUsageView() {
    $this->container->get('module_installer')->install(array('node'));
    \Drupal::service('router.builder')->rebuild();
    $file = $this->createFileEntity(array('uid' => $this->userAdmin));
    // @todo Next line causes an exception, core issue https://www.drupal.org/node/2462283
    $this->drupalLogin($this->userAdmin);

    // Check the usage links on the file overview.
    $this->drupalGet('admin/content/files');
    $this->assertSession()->linkExists('0 places');
    $this->assertSession()->linkNotExists('1 place');

    // Check the usage view.
    $this->clickLink('0 places');
    $this->assertSession()->pageTextContains('This file is not currently used.');

    // Attach a file field to article nodes.
    $content_type = $this->drupalCreateContentType();
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'used_file',
      'entity_type' => 'node',
      'type' => 'file',
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'entity_type' => 'node',
      'bundle' => $content_type->id(),
    ));
    $field_instance->save();

    // Create a node using a file.
    $node = Node::create(array(
      'title' => 'An article that uses a file',
      'type' => $content_type->id(),
      'used_file' => array(
        'target_id' => $file->id(),
        'display' => 1,
        'description' => '',
      ),
    ));
    $node->save();
    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    \Drupal::entityTypeManager()->getStorage('file')->resetCache();

    // Check that the usage link is updated.
    $this->drupalGet('admin/content/files');
    $this->assertSession()->linkExists('1 place');

    // Check that the using node shows up on the usage view.
    $this->clickLink('1 place');
    $this->assertSession()->linkExists('An article that uses a file');

    // Check local tasks.
    $this->clickLink('View');
    $this->assertSession()->statusCodeEquals(200);
    $this->clickLink('Usage');
    $this->assertSession()->statusCodeEquals(200);
  }
}
