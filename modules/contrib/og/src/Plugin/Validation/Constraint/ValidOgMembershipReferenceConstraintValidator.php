<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Og;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks if referenced entities are valid.
 */
class ValidOgMembershipReferenceConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(protected readonly EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemInterface $value */
    if (!isset($value)) {
      return;
    }

    $entity = $this->entityTypeManager
      ->getStorage($value->getFieldDefinition()->getFieldStorageDefinition()->getSetting('target_type'))
      ->load($value->get('target_id')->getValue());

    if (!$entity) {
      // Entity with that entity ID does not exists. This could happen if a
      // stale entity is passed for validation.
      return;
    }

    $params['%label'] = $entity->label();

    if (!Og::isGroup($entity->getEntityTypeId(), $entity->bundle())) {
      $this->context->addViolation($constraint->notValidGroup, $params);
    }
  }

}
