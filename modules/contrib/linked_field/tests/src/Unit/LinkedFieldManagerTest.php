<?php

namespace Drupal\Tests\linked_field\Unit;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldItemList;
use Drupal\linked_field\LinkedFieldManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Utility\Token;
use Drupal\Core\Path\PathValidator;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * Test linked field manager.
 *
 * @group linked_field
 */
class LinkedFieldManagerTest extends UnitTestCase {

  /**
   * Tests getDestinationFields().
   */
  public function testGetDestinationFields(): void {
    $entityTypeManager = $this->createStub(EntityTypeManager::class);
    $entityFieldManager = $this->createStub(EntityFieldManager::class);
    $definition = $this->createStub(EntityTypeInterface::class);

    $firstField = $this->createMock(BaseFieldDefinition::class);
    $firstField->expects($this->once())->method('getType')->willReturn('link');
    $firstField->expects($this->once())->method('getLabel')->willReturn('First field');

    $secondField = $this->createMock(BaseFieldDefinition::class);
    $secondField->expects($this->once())->method('getType')->willReturn('list_float');
    $secondField->expects($this->once())->method('getLabel')->willReturn('Second field');

    $thirdField = $this->createMock(BaseFieldDefinition::class);
    $thirdField->expects($this->once())->method('getType')->willReturn('number');
    $thirdField->expects($this->never())->method('getLabel')->willReturn('Third field');

    $definition->method('getKey')->willReturn('title');
    $entityTypeManager->method('getDefinition')->willReturn($definition);
    $entityFieldManager->method('getFieldDefinitions')->willReturn([
      'field_link' => $firstField,
      'field_list_float' => $secondField,
      'field_number' => $thirdField,
    ]);

    $linkedFieldManager = new LinkedFieldManager(
      $this->createStub(ConfigFactory::class),
      $this->createStub(PathValidator::class),
      $this->createStub(Token::class),
      $entityFieldManager,
      $entityTypeManager,
    );

    $field_names = $linkedFieldManager->getDestinationFields('node', 'article');

    $this->assertEquals([
      'field_link' => 'First field (field_link)',
      'field_list_float' => 'Second field (field_list_float)',
    ], $field_names);
  }

  /**
   * Test getFieldValue().
   *
   * @dataProvider providerTestGetFieldValue
   */
  public function testGetFieldValue(string $field_type, array $field_value, string $expected_uri) {
    $linkedFieldManager = new LinkedFieldManager(
      $this->createStub(ConfigFactory::class),
      $this->createStub(PathValidator::class),
      $this->createStub(Token::class),
      $this->createStub(EntityFieldManager::class),
      $this->createStub(EntityTypeManager::class),
    );

    $fieldItemList = $this->createMock(FieldItemList::class);
    $fieldDefinition = $this->createMock(BaseFieldDefinition::class);

    $fieldItemList->method('getValue')->willReturn($field_value);
    $fieldItemList->method('getFieldDefinition')->willReturn($fieldDefinition);

    $fieldDefinition->expects($this->once())->method('getType')->willReturn($field_type);

    $uri = $linkedFieldManager->getFieldValue($fieldItemList);
    $this->assertEquals($expected_uri, $uri);
  }

  /**
   * Tests getFieldItemAttributes().
   */
  public function testGetFieldItemAttributes() {
    $linkedFieldManager = new LinkedFieldManager(
      $this->createStub(ConfigFactory::class),
      $this->createStub(PathValidator::class),
      $this->createStub(Token::class),
      $this->createStub(EntityFieldManager::class),
      $this->createStub(EntityTypeManager::class),
    );

    $value = 'field_text';
    $delta = 0;

    $entity = $this->createMock(Node::class);
    $field = $this->createMock(FieldItemList::class);
    $fieldData = $this->createMock(TypedDataInterface::class);

    $entity->expects($this->once())->method('get')->with($value)->willReturn($field);
    $field->expects($this->once())->method('isEmpty')->willReturn(FALSE);
    $field->expects($this->once())->method('get')->with($delta)->willReturn($fieldData);
    $fieldData->expects($this->once())->method('getValue')->willReturn([
      'uri' => 'internal:/node',
      'title' => 'Node',
      'options' => [
        'attributes' => [
          'class' => [
            'test-class',
          ],
        ],
      ],
    ]);

    $context['entity'] = $entity;

    $attributes = $linkedFieldManager->getFieldItemAttributes($value, $delta, $context);

    $this->assertEquals([
      'class' => [
        'test-class',
      ],
    ], $attributes);
  }

  /**
   * Data provider for testGetFieldValue().
   */
  public static function providerTestGetFieldValue(): array {
    return [
      [
        'field_type' => 'link',
        'field_value' => [
          [
            'uri' => 'internal:/node/1',
          ],
        ],
        'expected_uri' => 'internal:/node/1',
      ],
      [
        'field_type' => 'text_long',
        'field_value' => [
          [
            'value' => '/page',
          ],
        ],
        'expected_uri' => '/page',
      ],
    ];
  }

}
