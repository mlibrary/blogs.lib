<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\file\Entity\File;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the photoswipe twig extension.
 *
 * @group photoswipe
 */
class PhotoswipeJsMiscTest extends PhotoswipeJsTestBase {
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the image field on the node 'article':
    $this->createImageField(
      'field_image',
      'node',
      'article',
      ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED],
      [],
      [],
      [
        'photoswipe_thumbnail_style_first' => '',
        'photoswipe_thumbnail_style' => '',
        'photoswipe_image_style' => '',
        'photoswipe_reference_image_field' => '',
        'photoswipe_view_mode' => '',
      ],
      'photoswipe_field_formatter',
    );
  }

  /**
   * Tests, if modifying the settings actually apply in js.
   */
  public function testSettingsApplyInJs() {
    $page = $this->getSession()->getPage();
    $driver = $this->getSession()->getDriver();
    $session = $this->assertSession();

    // Setup an image node:
    $fileFieldEntries = [];
    $file = File::create([
      'uri' => $this->getTestFiles('image')[0]->uri,
    ]);
    $file->save();
    $fileFieldEntries[] = [
      'target_id' => $file->id(),
      'alt' => 'Test alt',
      'title' => 'bla',
    ];

    $node = $this->createNode([
      'title' => 'Test',
      'type' => 'article',
      'field_image' => $fileFieldEntries,
    ]);
    $node->save();

    // Go to the node and see if the default settings apply:
    $this->drupalGet('/node/' . $node->id());
    $page->find('css', '.photoswipe-gallery a.photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $drupalJsSettings = $driver->evaluateScript('pswp.options');
    // Even if this is set to true, it will get disabled on non touch devices:
    $this->assertEquals($drupalJsSettings['allowPanToNext'], FALSE);
    $this->assertEquals($drupalJsSettings['arrowKeys'], TRUE);
    $this->assertEquals($drupalJsSettings['bgClickAction'], 'close');
    $this->assertEquals($drupalJsSettings['bgOpacity'], 0.8);
    $this->assertEquals($drupalJsSettings['clickToCloseNonZoomable'], TRUE);
    $this->assertEquals($drupalJsSettings['closeOnVerticalDrag'], TRUE);
    $this->assertEquals($drupalJsSettings['doubleTapAction'], 'zoom');
    $this->assertEquals($drupalJsSettings['easing'], 'cubic-bezier(.4,0,.22,1)');
    $this->assertEquals($drupalJsSettings['errorMsg'], 'The image could not be loaded.');
    $this->assertEquals($drupalJsSettings['escKey'], TRUE);
    $this->assertEquals($drupalJsSettings['hideAnimationDuration'], 333);
    $this->assertEquals($drupalJsSettings['imageClickAction'], 'zoom-or-close');
    $this->assertEquals($drupalJsSettings['indexIndicatorSep'], ' / ');
    $this->assertEquals($drupalJsSettings['maxWidthToAnimate'], 4000);
    $this->assertEquals($drupalJsSettings['initialZoomLevel'], 'fit');
    $this->assertEquals($drupalJsSettings['secondaryZoomLevel'], 2.5);
    $this->assertEquals($drupalJsSettings['maxZoomLevel'], 4);
    $this->assertEquals($drupalJsSettings['wheelToZoom'], FALSE);
    $this->assertEquals($drupalJsSettings['pinchToClose'], TRUE);
    $this->assertEquals($drupalJsSettings['trapFocus'], TRUE);
    $this->assertEquals($drupalJsSettings['preload'][0], 1);
    $this->assertEquals($drupalJsSettings['preload'][1], 2);
    $this->assertEquals($drupalJsSettings['preloaderDelay'], 2000);
    $this->assertEquals($drupalJsSettings['returnFocus'], TRUE);
    $this->assertEquals($drupalJsSettings['showAnimationDuration'], 333);
    $this->assertEquals($drupalJsSettings['showHideAnimationType'], 'zoom');
    $this->assertEquals($drupalJsSettings['spacing'], 0.1);
    $this->assertEquals($drupalJsSettings['tapAction'], 'toggle-controls');
    $this->assertEquals($drupalJsSettings['zoomAnimationDuration'], 333);

    // Go to the settings page and enable loading on non admin pages:
    $this->drupalGet('/admin/config/media/photoswipe');
    $page->fillField('showHideAnimationType', 'fade');
    $page->fillField('showAnimationDuration', '334');
    $page->fillField('hideAnimationDuration', '334');
    $page->fillField('zoomAnimationDuration', '334');
    $page->fillField('maxWidthToAnimate', '5000');
    $page->fillField('easing', 'cubic-bezier(.3,0,.14,1)');
    $page->fillField('bgOpacity', '0.12');
    $page->fillField('spacing', '0.2');
    $page->fillField('initialZoomLevel', '3');
    $page->fillField('secondaryZoomLevel', '4');
    $page->fillField('maxZoomLevel', '5');
    $page->checkField('wheelToZoom');
    $page->uncheckField('allowPanToNext');
    $page->uncheckField('loop');
    $page->uncheckField('pinchToClose');
    $page->uncheckField('clickToCloseNonZoomable');
    $page->uncheckField('closeOnVerticalDrag');
    $page->uncheckField('returnFocus');
    $page->uncheckField('escKey');
    $page->uncheckField('arrowKeys');
    $page->uncheckField('trapFocus');
    $page->selectFieldOption('imageClickAction', 'close');
    $page->selectFieldOption('tapAction', 'close');
    $page->selectFieldOption('doubleTapAction', 'close');
    $page->selectFieldOption('bgClickAction', 'zoom');
    $page->fillField('indexIndicatorSep', ' x ');
    $page->fillField('errorMsg', '<div class="test-error-class">Test image could not be loaded.</div>');
    $page->fillField('preloadBefore', '3');
    $page->fillField('preloadAfter', '3');
    $page->fillField('preloaderDelay', '3000');
    $page->pressButton('edit-submit');

    // Go to the node once again and check if the modified settings apply:
    $this->drupalGet('/node/' . $node->id());
    $page->find('css', '.photoswipe-gallery a.photoswipe')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $drupalJsSettings = $driver->evaluateScript('pswp.options');
    // Even if this is set to true, it will get disabled on non touch devices:
    $this->assertEquals($drupalJsSettings['allowPanToNext'], FALSE);
    $this->assertEquals($drupalJsSettings['arrowKeys'], FALSE);
    $this->assertEquals($drupalJsSettings['bgClickAction'], 'zoom');
    $this->assertEquals($drupalJsSettings['bgOpacity'], 0.12);
    $this->assertEquals($drupalJsSettings['clickToCloseNonZoomable'], FALSE);
    $this->assertEquals($drupalJsSettings['closeOnVerticalDrag'], FALSE);
    $this->assertEquals($drupalJsSettings['doubleTapAction'], 'close');
    $this->assertEquals($drupalJsSettings['easing'], 'cubic-bezier(.3,0,.14,1)');
    $this->assertEquals($drupalJsSettings['errorMsg'], '<div class="test-error-class">Test image could not be loaded.</div>');
    $this->assertEquals($drupalJsSettings['escKey'], FALSE);
    $this->assertEquals($drupalJsSettings['hideAnimationDuration'], 334);
    $this->assertEquals($drupalJsSettings['imageClickAction'], 'close');
    $this->assertEquals($drupalJsSettings['indexIndicatorSep'], ' x ');
    $this->assertEquals($drupalJsSettings['maxWidthToAnimate'], 5000);
    $this->assertEquals($drupalJsSettings['initialZoomLevel'], 3);
    $this->assertEquals($drupalJsSettings['secondaryZoomLevel'], 4);
    $this->assertEquals($drupalJsSettings['maxZoomLevel'], 5);
    $this->assertEquals($drupalJsSettings['pinchToClose'], FALSE);
    $this->assertEquals($drupalJsSettings['wheelToZoom'], TRUE);
    $this->assertEquals($drupalJsSettings['preload'][0], 3);
    $this->assertEquals($drupalJsSettings['preload'][1], 3);
    $this->assertEquals($drupalJsSettings['preloaderDelay'], 3000);
    $this->assertEquals($drupalJsSettings['returnFocus'], FALSE);
    $this->assertEquals($drupalJsSettings['trapFocus'], FALSE);
    $this->assertEquals($drupalJsSettings['showAnimationDuration'], 334);
    $this->assertEquals($drupalJsSettings['showHideAnimationType'], 'fade');
    $this->assertEquals($drupalJsSettings['spacing'], 0.2);
    $this->assertEquals($drupalJsSettings['tapAction'], 'close');
    $this->assertEquals($drupalJsSettings['zoomAnimationDuration'], 334);
  }

  /*
   * @todo Add test for ensuring "photoswipe_always_load_non_admin" works as
   * expected.
   */

}
