<?php

namespace Drupal\Tests\linked_field\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests linked field functional.
 *
 * @group linked_field
 */
class LinkedFieldTest extends BrowserTestBase {

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
    'node',
    'field',
    'linked_field',
    'token',
    'link',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Tests linked field with token.
   */
  public function testLinkedFieldWithToken(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Text',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_another_text',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_another_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Another text',
    ])->save();

    $entityDisplay = EntityViewDisplay::load('node.article.default');
    $entityDisplay->setComponent('field_another_text', [
      'type' => 'string',
      'third_party_settings' => [
        'linked_field' => [
          'linked' => '1',
          'type' => 'custom',
          'destination' => '[node:field_text]',
          'advanced' => [
            'class' => 'linked-field-class',
          ],
          'token' => [],
        ],
      ],
    ]);
    $entityDisplay->save();

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test',
      'field_text' => '/custom-page-path',
      'field_another_text' => 'Second text field',
    ]);

    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->elementExists('css', 'a.linked-field-class');
    $this->assertSession()->pageTextContains('Second text field');
  }

  /**
   * Tests linked field with field.
   */
  public function testLinkedFieldWithField(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'type' => 'link',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Link',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_another_text',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_another_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Another text',
    ])->save();

    $entityDisplay = EntityViewDisplay::load('node.article.default');
    $entityDisplay->setComponent('field_another_text', [
      'type' => 'string',
      'third_party_settings' => [
        'linked_field' => [
          'linked' => '1',
          'type' => 'field',
          'destination' => 'field_link',
          'advanced' => [
            'class' => 'linked-field-class',
          ],
          'token' => [],
        ],
      ],
    ]);
    $entityDisplay->save();

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test',
      'field_another_text' => 'Second text field',
      'field_link' => 'internal:/custom-page-path',
    ]);

    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->elementExists('css', 'a.linked-field-class');
    $this->assertSession()->pageTextContains('Second text field');
  }

  /**
   * Tests that field content is not evaluated as a Twig template.
   */
  public function testFieldContentIsNotEvaluatedAsTwig(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_another_text',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_another_text',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Another text',
    ])->save();

    $entityDisplay = EntityViewDisplay::load('node.article.default');
    $entityDisplay->setComponent('field_another_text', [
      'type' => 'string',
      'third_party_settings' => [
        'linked_field' => [
          'linked' => '1',
          'type' => 'custom',
          'destination' => '/custom-page-path',
          'advanced' => [
            'class' => 'linked-field-class',
          ],
          'token' => [],
        ],
      ],
    ]);
    $entityDisplay->save();

    $node = $this->createNode([
      'type' => 'article',
      'title' => 'Test',
      'field_another_text' => 'Result: {{ 7*7 }}',
    ]);

    $this->drupalGet('node/' . $node->id());

    $this->assertSession()->elementExists('css', 'a.linked-field-class');
    $this->assertSession()->elementTextContains('css', 'a.linked-field-class', 'Result: {{ 7*7 }}');
    $this->assertSession()->pageTextNotContains('Result: 49');
  }

}
