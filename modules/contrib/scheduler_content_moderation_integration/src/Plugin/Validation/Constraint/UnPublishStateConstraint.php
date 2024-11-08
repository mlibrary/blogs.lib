<?php

namespace Drupal\scheduler_content_moderation_integration\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Validates scheduler un-publish state.
 *
 * @Constraint(
 *   id = "SchedulerUnPublishState",
 *   label = @Translation("Scheduler un-publish state validation", context = "Validation"),
 *   type = "string"
 * )
 */
class UnPublishStateConstraint extends Constraint {

  /**
   * Invalid publish to publish transition message.
   *
   * Message to display when the transition between the scheduled publishing
   * state and the scheduled un-publishing state is not a valid transition.
   *
   * @var string
   */
  public string $invalidPublishToUnPublishTransitionMessage = 'The scheduled un-publishing state of %unpublish_state is not a valid transition from the scheduled publishing state of %publish_state.';

  /**
   * Invalid unpublish transition message.
   *
   * Message to display when the transition between the current moderation state
   * and the scheduled un-publishing state is not a valid transition.
   *
   * @var string
   */
  public string $invalidUnPublishTransitionMessage = 'The scheduled un-publishing state of %unpublish_state is not a valid transition from the current moderation state of %content_state for this content.';

}
