<?php

namespace Drupal\Tests\photoswipe\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * This class provides methods specifically for testing something.
 *
 * @group photoswipe
 */
class PhotoswipeSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'photoswipe',
    'node',
    'test_page_test',
  ];

  /**
   * A user with authenticated permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $user;

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->config('system.site')->set('page.front', '/test-page')->save();
    $this->user = $this->drupalCreateUser([]);
    $this->adminUser = $this->drupalCreateUser([]);
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests functionality of the settings page.
   */
  public function testSettingsChange() {
    $session = $this->assertSession();
    $page = $this->getSession()->getPage();
    // Go to the settings page:
    $this->drupalGet('/admin/config/media/photoswipe');
    $session->statusCodeEquals(200);
    // Check if the default settings are present:
    $page->hasUncheckedField('enable_cdn');
    $page->hasUncheckedField('photoswipe_always_load_non_admin');
    $session->fieldValueEquals('showHideAnimationType', 'zoom');
    $session->fieldValueEquals('showAnimationDuration', 333);
    $session->fieldValueEquals('hideAnimationDuration', 333);
    $session->fieldValueEquals('zoomAnimationDuration', 333);
    $session->fieldValueEquals('maxWidthToAnimate', 4000);
    $session->fieldValueEquals('easing', 'cubic-bezier(.4,0,.22,1)');
    $session->fieldValueEquals('bgOpacity', 0.8);
    $session->fieldValueEquals('spacing', 0.1);
    $session->fieldValueEquals('initialZoomLevel', 'fit');
    $session->fieldValueEquals('secondaryZoomLevel', 2.5);
    $session->fieldValueEquals('maxZoomLevel', 4);
    $page->hasCheckedField('allowPanToNext');
    $page->hasCheckedField('loop');
    $page->hasUncheckedField('wheelToZoom');
    $page->hasCheckedField('pinchToClose');
    $page->hasCheckedField('clickToCloseNonZoomable');
    $page->hasCheckedField('closeOnVerticalDrag');
    $page->hasCheckedField('trapFocus');
    $page->hasCheckedField('returnFocus');
    $page->hasCheckedField('escKey');
    $page->hasCheckedField('arrowKeys');
    $session->fieldValueEquals('imageClickAction', 'zoom-or-close');
    $session->fieldValueEquals('tapAction', 'toggle-controls');
    $session->fieldValueEquals('doubleTapAction', 'zoom');
    $session->fieldValueEquals('bgClickAction', 'close');
    $session->fieldValueEquals('indexIndicatorSep', ' / ');
    $session->fieldValueEquals('errorMsg', 'The image could not be loaded.');
    $session->fieldValueEquals('preloadBefore', 1);
    $session->fieldValueEquals('preloadAfter', 2);
    $session->fieldValueEquals('preloaderDelay', 2000);
    $session->fieldValueEquals('mainClass', '');
    // Change the settings values:
    $page->checkField('enable_cdn');
    $page->checkField('photoswipe_always_load_non_admin');
    $page->fillField('showHideAnimationType', 'fade');
    $page->fillField('showAnimationDuration', 12345);
    $page->fillField('hideAnimationDuration', 12345);
    $page->fillField('zoomAnimationDuration', 12345);
    $page->fillField('maxWidthToAnimate', 12345);
    $page->fillField('easing', '12345');
    $page->fillField('bgOpacity', 0.12);
    $page->fillField('spacing', 0.12);
    $page->fillField('initialZoomLevel', 12.3);
    $page->fillField('secondaryZoomLevel', 12.3);
    $page->fillField('maxZoomLevel', 12.3);
    $page->uncheckField('allowPanToNext');
    $page->uncheckField('loop');
    $page->checkField('wheelToZoom');
    $page->uncheckField('pinchToClose');
    $page->uncheckField('clickToCloseNonZoomable');
    $page->uncheckField('closeOnVerticalDrag');
    $page->uncheckField('trapFocus');
    $page->uncheckField('returnFocus');
    $page->uncheckField('escKey');
    $page->uncheckField('arrowKeys');
    $page->selectFieldOption('imageClickAction', 'close');
    $page->selectFieldOption('tapAction', 'close');
    $page->selectFieldOption('doubleTapAction', 'close');
    $page->selectFieldOption('bgClickAction', 'zoom');
    $page->fillField('indexIndicatorSep', '12345');
    $page->fillField('errorMsg', '12345');
    $page->fillField('preloadBefore', 12345);
    $page->fillField('preloadAfter', 12345);
    $page->fillField('preloaderDelay', 12345);
    $page->fillField('mainClass', 'abcde');
    // Submit the settings and re-enter the settings page:
    $page->pressButton('edit-submit');
    $session->statusCodeEquals(200);
    $this->drupalGet('/admin/config/media/photoswipe');
    $session->statusCodeEquals(200);
    // Check if the settings have changed accordingly:
    $page->hasCheckedField('enable_cdn');
    $page->hasCheckedField('photoswipe_always_load_non_admin');
    $session->fieldValueEquals('showHideAnimationType', 'fade');
    $session->fieldValueEquals('showAnimationDuration', 12345);
    $session->fieldValueEquals('hideAnimationDuration', 12345);
    $session->fieldValueEquals('zoomAnimationDuration', 12345);
    $session->fieldValueEquals('maxWidthToAnimate', 12345);
    $session->fieldValueEquals('easing', '12345');
    $session->fieldValueEquals('bgOpacity', 0.12);
    $session->fieldValueEquals('spacing', 0.12);
    $session->fieldValueEquals('initialZoomLevel', 12.3);
    $session->fieldValueEquals('secondaryZoomLevel', 12.3);
    $session->fieldValueEquals('maxZoomLevel', 12.3);
    $page->hasUncheckedField('allowPanToNext');
    $page->hasUncheckedField('loop');
    $page->hasCheckedField('wheelToZoom');
    $page->hasUncheckedField('pinchToClose');
    $page->hasUncheckedField('clickToCloseNonZoomable');
    $page->hasUncheckedField('closeOnVerticalDrag');
    $page->hasUncheckedField('trapFocus');
    $page->hasUncheckedField('returnFocus');
    $page->hasUncheckedField('escKey');
    $page->hasUncheckedField('arrowKeys');
    $session->fieldValueEquals('imageClickAction', 'close');
    $session->fieldValueEquals('tapAction', 'close');
    $session->fieldValueEquals('doubleTapAction', 'close');
    $session->fieldValueEquals('bgClickAction', 'zoom');
    $session->fieldValueEquals('indexIndicatorSep', '12345');
    $session->fieldValueEquals('errorMsg', '12345');
    $session->fieldValueEquals('preloadBefore', 12345);
    $session->fieldValueEquals('preloadAfter', 12345);
    $session->fieldValueEquals('preloaderDelay', 12345);
    $session->fieldValueEquals('mainClass', 'abcde');
  }

}
