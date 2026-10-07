<?php

namespace Drupal\Tests\linked_field\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests config form.
 *
 * @group linked_field
 */
class ConfigFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'linked_field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer linked_field']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Test form submits with normal data.
   */
  public function testSubmitWithNormalData() {
    $this->drupalGet('/admin/config/linked_field/config');

    $this->submitForm([
      'config' => "attributes:
  title:
    label: ''
    description: ''",
    ], 'Save configuration');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('The configuration options have been saved.');

    // Ensure that config successfully updated.
    $updatedConfig = \Drupal::configFactory()->getEditable('linked_field.config')->get('attributes');
    $this->assertArrayHasKey('title', $updatedConfig);
    $this->assertArrayHasKey('label', $updatedConfig['title']);
    $this->assertArrayHasKey('description', $updatedConfig['title']);
    $this->assertEmpty($updatedConfig['title']['label']);
    $this->assertEmpty($updatedConfig['title']['description']);
  }

  /**
   * Test form submits with not valid YAML.
   */
  public function testSubmitWithNotValidYaml() {
    $this->drupalGet('/admin/config/linked_field/config');

    $this->submitForm([
      'config' => "attributes:
  title:
    label: ''
    description: ''
attributes:
  text: ''",
    ], 'Save configuration');

    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Duplicate key "attributes" detected at line');
  }

  /**
   * Test config form with broken input values.
   *
   * @dataProvider providerBrokenInputData
   */
  public function testSubmitWithBrokenData(string $broken_input) {
    $this->drupalGet('/admin/config/linked_field/config');

    $this->submitForm([
      'config' => $broken_input,
    ], 'Save configuration');

    // Ensure that 500 error during submit didn't appear.
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Data provider for testSubmitWithBrokenData().
   */
  public static function providerBrokenInputData(): array {
    return [
      ['broken_input' => 'attributes:'],
      ['broken_input' => ''],
      ['broken_input' => ' '],
      ['broken_input' => 'not_attributes:'],
      ['broken_input' => 'just_string'],
    ];
  }

}
