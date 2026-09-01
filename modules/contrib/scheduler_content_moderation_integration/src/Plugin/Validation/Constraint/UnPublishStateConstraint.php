<?php

namespace Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Validates scheduler un-publish state.
 */
#[Constraint(
  id: 'SchedulerUnPublishState',
  label: new TranslatableMarkup('Scheduler un-publish state validation'),
  type: 'string',
)]
class UnPublishStateConstraint extends SymfonyConstraint {

  /**
   * Invalid publish to publish a transition message.
   *
   * Message to display when the transition between the scheduled publishing
   * state and the scheduled unpublishing state is not a valid transition.
   *
   * @var string
   */
  public string $invalidPublishToUnPublishTransitionMessage = 'The scheduled un-publishing state of %unpublish_state is not a valid transition from the scheduled publishing state of %publish_state.';

  /**
   * Invalid unpublished transition message.
   *
   * Message to display when the transition between the current moderation state
   * and the scheduled unpublishing state is not a valid transition.
   *
   * @var string
   */
  public string $invalidUnPublishTransitionMessage = 'The scheduled un-publishing state of %unpublish_state is not a valid transition from the current moderation state of %content_state for this content.';

  /**
   * Message when the current moderation state matches the scheduled state.
   *
   * @var string
   */
  public string $sameStateMessage = 'The content is already in the %moderation_state state. Either remove the scheduled un-publish date or change the current moderation state to a different state.';

}
