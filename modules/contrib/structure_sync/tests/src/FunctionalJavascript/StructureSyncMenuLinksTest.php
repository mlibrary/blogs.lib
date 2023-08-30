<?php

namespace Drupal\Tests\structure_sync\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\structure_sync\Traits\StructureSyncMenuLinksSetUpTrait;

/**
 * Tests basic import and export functionalities.
 *
 * @group structure_sync
 */
class StructureSyncMenuLinksTest extends WebDriverTestBase {

  use StructureSyncMenuLinksSetUpTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'structure_sync',
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The menu link entity storage handler.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createSetUpContent();

    // Log in an administrative user.
    $this->drupalLogin($this->drupalCreateUser([
      'administer site configuration',
      'administer menu',
    ]));
  }

  /**
   * Test importing and exporting of menu items entities.
   */
  public function testMenuLinksExportImportUsingAdmin(): void {
    $this->drupalGet('admin/structure/structure-sync/menu-links');

    $page = $this->getSession()->getPage();
    $page->uncheckField('Arc');
    $this->click('#edit-export-menu-links');

    // Menu "arc" should not be exported.
    $menus = $this->config('structure_sync.data')->get('menus');
    foreach ($menus as $menu) {
      $this->assertNotContains('arc', $menu);
    }

    $this->assertCount(5, $menus);

    // Editing the "Oxygen" menu link.
    $oxygenMenuLinks = $this->menuLinkStorage->loadByProperties(['uuid' => 'd2385dc8-e70d-490f-8ec7-d95c828c98ac']);
    reset($oxygenMenuLinks)->set('description', 'Oxygen')->save();

    // Deleting the "Tig gas" menu link.
    $tigGasMenuLinkBefore = $this->menuLinkStorage->loadByProperties(['uuid' => '48062c1c-132b-476e-bc3c-8511fb8896e4']);
    $this->menuLinkStorage->delete($tigGasMenuLinkBefore);
    $this->assertEmpty($this->menuLinkStorage->loadByProperties(['uuid' => '48062c1c-132b-476e-bc3c-8511fb8896e4']));

    // Adding an "Electrode" menu link.
    MenuLinkContent::create([
      'title' => 'Electrode',
      'link' => 'https://en.wikipedia.org/wiki/Electrode',
      'menu_name' => 'arc',
      'uuid' => '9a49342a-45e0-4d15-a251-83d704371c79',
    ])->save();

    $this->click('#edit-import-menus-safe');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Deleted menu item should be imported.
    $tigGasMenuLink = $this->menuLinkStorage->loadByProperties(['uuid' => '48062c1c-132b-476e-bc3c-8511fb8896e4']);
    $this->assertNotEmpty($tigGasMenuLink);

    // "MIG" and "Arc" should not be deleted during the process.
    $this->assertNotEmpty($this->menuLinkStorage->loadByProperties(['uuid' => 'a568e500-850a-4445-9e97-bf3b20aee779']));
    $this->assertNotEmpty($this->menuLinkStorage->loadByProperties(['uuid' => '73e0f490-803c-439a-a15c-2d1c3d4ceac7']));

    // "Oxygen" menu link should not be updated by a safe import.
    $oxygenMenuLinks = $this->menuLinkStorage->loadByProperties(['uuid' => 'd2385dc8-e70d-490f-8ec7-d95c828c98ac']);
    $this->assertEquals('Oxygen', reset($oxygenMenuLinks)->description->value);

    // "Electrode" menu link should not be deleted by a safe import.
    $electrodeMenuLinks = $this->menuLinkStorage->loadByProperties(['uuid' => '9a49342a-45e0-4d15-a251-83d704371c79']);
    $this->assertNotEmpty($electrodeMenuLinks);

    // Importing menus using full mode.
    $this->click('#edit-import-menus-full');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // "Electrode" menu link should be deleted by full import.
    $electrodeMenuLinks = $this->menuLinkStorage->loadByProperties(['uuid' => '9a49342a-45e0-4d15-a251-83d704371c79']);
    $this->assertEmpty($electrodeMenuLinks);

    // "Tig gas" should be correctly imported.
    $tigGasMenuLink = $this->menuLinkStorage->loadByProperties(['uuid' => '48062c1c-132b-476e-bc3c-8511fb8896e4']);
    $this->assertNotEmpty($tigGasMenuLink);

    // Importing menus using full mode.
    $this->click('#edit-import-menus-force');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // "Oxygen" menu link should be updated by a force import.
    $oxygenMenuLinks = $this->menuLinkStorage->loadByProperties(['uuid' => 'd2385dc8-e70d-490f-8ec7-d95c828c98ac']);
    $this->assertEquals('Oxygen gas', reset($oxygenMenuLinks)->description->value);
  }

}
