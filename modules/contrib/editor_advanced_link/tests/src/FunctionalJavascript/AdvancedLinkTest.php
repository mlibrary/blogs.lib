<?php

namespace Drupal\Tests\editor_advanced_link\FunctionalJavascript;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\editor\Entity\Editor;
use Drupal\editor_advanced_link\Plugin\CKEditor5Plugin\AdvancedLink;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\editor_advanced_link\Plugin\CKEditor5Plugin\AdvancedLink
 * @group editor_advanced_link
 * @group ckeditor5
 * @internal
 */
class AdvancedLinkTest extends WebDriverTestBase {

  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'editor_advanced_link',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <em> <a href>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'link',
            'bold',
            'italic',
          ],
        ],
        'plugins' => [
          'editor_advanced_link_link' => AdvancedLink::DEFAULT_CONFIGURATION,
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Create a sample node to test AdvancedLink on.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p><a href="https://en.wikipedia.org/wiki/Llama">Llamas</a> are cool!</p>',
        'format' => 'test_format',
      ],
    ])->save();

    $this->drupalLogin($this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]));
  }

  /**
   * Data provider for the test method.
   */
  public static function providerTest(): array {
    return [
      '<a aria-label>' => [
        'attribute_name' => 'aria-label',
        'expected_input_label' => 'ARIA label',
        'is_grouped' => TRUE,
      ],
      '<a title>' => [
        'attribute_name' => 'title',
        'expected_input_label' => 'Title',
        'is_grouped' => TRUE,
      ],
      '<a class>' => [
        'attribute_name' => 'class',
        'expected_input_label' => 'CSS classes',
        'is_grouped' => TRUE,
      ],
      '<a id>' => [
        'attribute_name' => 'id',
        'expected_input_label' => 'ID',
        'is_grouped' => TRUE,
      ],
      '<a rel>' => [
        'attribute_name' => 'rel',
        'expected_input_label' => 'Link relationship',
        'is_grouped' => TRUE,
      ],
    ];
  }

  /**
   * Tests that AdvancedLink enables setting additional link attributes.
   *
   * @dataProvider providerTest
   */
  public function test(string $attribute_name, string $expected_input_label, bool $is_grouped = FALSE, bool $is_button = FALSE) {
    $this->configureExtraAttribute($attribute_name);

    $this->drupalGet('/node/1/edit');
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $link = $assert_session->waitForElementVisible('css', '.ck-content a[href="https://en.wikipedia.org/wiki/Llama"]', 1000);

    // Confirm the attribute we'll set is not yet present.
    $this->assertStringNotContainsString($attribute_name, $this->getEditorDataAsHtmlString());

    // Assert structure of link form balloon.
    $link->click();
    $this->assertVisibleBalloon('.ck-link-toolbar');
    $this->getBalloonButton('Edit link')->click();
    $balloon = $this->assertVisibleBalloon('.ck-link-form');

    $field_parent = $balloon;
    if ($is_grouped) {
      $field_parent = $balloon->find('css', '.ck-collapsible');
      $this->assertNotEmpty($field_parent, 'Group has been found');
      // Open the group.
      $field_parent->find('css', '.ck-button[aria-expanded]')->click();
      $this->assertFalse($field_parent->hasClass('ck-collapsible_collapsed'), 'Group is open');
    }

    if (!$is_button) {
      $this->assertTrue($field_parent->hasField($expected_input_label), 'Field has been found');
    }
    else {
      $this->assertTrue($field_parent->hasButton($expected_input_label), 'Button has been found');
    }
    // Three inputs: 1 for the link URL, 1 for the link text, 1 for the
    // attribute editable through AdvancedLink (either an input or a button for
    // the target attribute).
    $this->assertCount(3, $balloon->findAll('css', 'input, .ck-switchbutton'));

    // Confirm we can set the attribute using AdvancedLink's UI.
    if (!$is_button) {
      $field_parent->fillField($expected_input_label, 'foobarbaz');
    }
    else {
      $field_parent->pressButton($expected_input_label);
    }
    $balloon->find('css', '.ck-button-action')->click();
    $this->assertStringContainsString($attribute_name, $this->getEditorDataAsHtmlString());
  }

  /**
   * Test the target attribute as it's handled by CKEditor specifically.
   */
  public function testTargetAttribute() {
    $attribute_name = 'target';
    $expected_input_label = 'Open in new window';

    $this->configureExtraAttribute($attribute_name);

    $this->drupalGet('/node/1/edit');
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $link = $assert_session->waitForElementVisible('css', '.ck-content a[href="https://en.wikipedia.org/wiki/Llama"]', 1000);

    // Confirm the attribute we'll set is not yet present.
    $this->assertStringNotContainsString($attribute_name, $this->getEditorDataAsHtmlString());

    // Assert structure of link form balloon.
    $link->click();
    $this->assertVisibleBalloon('.ck-link-toolbar');
    $this->getBalloonButton('Link properties')->click();
    $balloon = $this->assertVisibleBalloon('.ck-link-properties');

    // Push the target button.
    $this->assertTrue($balloon->hasButton($expected_input_label), 'Button has been found');
    $balloon->pressButton($expected_input_label);

    // Force focus out of the balloon.
    $this->getSession()->getPage()->find('css', '.ck-content')->click();

    $this->assertStringContainsString($attribute_name, $this->getEditorDataAsHtmlString());
  }

  /**
   * Configure text field and editor to allow editing the given attribute.
   *
   * @param string $attribute_name
   *   The attribute name.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function configureExtraAttribute(string $attribute_name) {
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $settings['plugins']['editor_advanced_link_link']['enabled_attributes'][] = $attribute_name;
    $editor->setSettings($settings)
      ->save();

    $format = $editor->getFilterFormat();
    $filter_html_config = $format->filters('filter_html')
      ->getConfiguration();
    $currentRestrictions = HtmlRestrictions::fromString($filter_html_config['settings']['allowed_html']);
    $newRestrictions = HtmlRestrictions::fromString(AdvancedLink::getAllowedHtmlForSupportedAttribute($attribute_name));
    $filter_html_config['settings']['allowed_html'] = $currentRestrictions->merge($newRestrictions)->toFilterHtmlAllowedTagsString();
    $format
      ->setFilterConfig('filter_html', $filter_html_config)
      ->save();

    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));
  }

}
