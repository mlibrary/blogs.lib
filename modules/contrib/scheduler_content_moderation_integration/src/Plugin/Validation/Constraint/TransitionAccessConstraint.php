<?php

namespace Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates content moderation transition access.
 */
#[Constraint(
  id: 'SchedulerModerationTransitionAccess',
  label: new TranslatableMarkup('Scheduler content moderation transition access validation'),
  type: 'string',
)]
class TransitionAccessConstraint extends SymfonyConstraint {

  /**
   * No access message.
   *
   * @var string
   */
  public string $noAccessMessage = 'You do not have access to transition from %original_state to %new_state';

}
