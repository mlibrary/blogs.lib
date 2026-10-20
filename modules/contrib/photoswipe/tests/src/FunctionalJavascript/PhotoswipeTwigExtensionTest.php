<?php

namespace Drupal\Tests\photoswipe\FunctionalJavascript;

/**
 * Tests the photoswipe twig extension.
 *
 * @group photoswipe
 */
class PhotoswipeTwigExtensionTest extends PhotoswipeJsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'photoswipe_twig_extension_test',
  ];

  /**
   * Tests the "attach_photoswipe()" theme function without options override.
   */
  public function testTwigFunctionAttachPhotoswipeNoOverride() {
    $session = $this->assertSession();
    $this->drupalGet('/photoswipe-twig-extension-test/function-test');

    $session->elementExists('css', 'script[src*="/photoswipe.init.js"]');
    $session->elementExists('css', 'script[src*="/prepare-galleries.js"]');
    $session->elementExists('css', 'script[src*="/photoswipe.umd.min.js"]');
    $session->elementExists('css', 'script[src*="/photoswipe-lightbox.umd.min.js"]');

    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe');
    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe > img[alt="Test123"]');

    $this->getSession()->getPage()->find('css', '.photoswipe-gallery a.photoswipe:not(.hidden)')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    $session->elementExists('css', '#pswp__items img.pswp__img');
  }

  /**
   * Tests the "attach_photoswipe()" theme function with options override.
   */
  public function testTwigFunctionAttachPhotoswipeOverrideOptions() {
    // Set the opacity to 0.8:
    $this->config('photoswipe.settings')->set('options.bgOpacity', 0.8)->save();
    $driver = $this->getSession()->getDriver();
    $session = $this->assertSession();
    $this->drupalGet('/photoswipe-twig-extension-test/function-test-options-overridden');

    $session->elementExists('css', 'script[src*="/photoswipe.init.js"]');
    $session->elementExists('css', 'script[src*="/prepare-galleries.js"]');
    $session->elementExists('css', 'script[src*="/photoswipe.umd.min.js"]');
    $session->elementExists('css', 'script[src*="/photoswipe-lightbox.umd.min.js"]');

    $session->elementExists('css', 'div.photoswipe-gallery a.photoswipe');

    $this->getSession()->getPage()->find('css', '.photoswipe-gallery a.photoswipe:not(.hidden)')->click();
    $this->assertNotNull($session->waitForElementVisible('css', '.pswp'));
    // Note, that "#pswp__items" always has 3 children but the first one
    // doesn't have an image if the gallery only consists out of 2 images:
    $session->elementTextEquals('css', '.pswp .pswp__scroll-wrap .pswp__counter', '1 / 2');
    $session->elementExists('css', '#pswp__items img.pswp__img');
    // See if our theme function overrides the opacity value:
    $this->assertEquals(0.2, $driver->evaluateScript('pswp.options.bgOpacity'));
  }

}
