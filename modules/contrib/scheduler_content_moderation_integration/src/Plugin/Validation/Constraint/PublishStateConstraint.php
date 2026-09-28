<?php

namespace Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates scheduler publish state.
 */
#[Constraint(
  id: 'SchedulerPublishState',
  label: new TranslatableMarkup('Scheduler publish state validation'),
  type: 'string',
)]
class PublishStateConstraint extends SymfonyConstraint {

  /**
   * Publish state invalid transition message.
   *
   * Message to display on invalid publishing transition between the current
   * moderation state to the specified publishing state.
   *
   * @var string
   */
  public string $invalidTransitionMessage = 'The scheduled publishing state of %publish_state is not a valid transition from the current moderation state of %content_state for this content.';

  /**
   * Message when the current moderation state matches the scheduled state.
   *
   * @var string
   */
  public string $sameStateMessage = 'The content is already being saved as %moderation_state. Either remove the scheduled publish date or change the current moderation state to a different state.';

}
