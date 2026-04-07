<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\scheduler\SchedulerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SchedulerUnpublishOn constraint.
 *
 * SchedulerPastDatesTest, SchedulerRequiredTest and SchedulerValidationTest
 * provide test coverage.
 */
class SchedulerUnpublishOnConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Scheduler manager service.
   *
   * @var \Drupal\scheduler\SchedulerManager
   */
  protected $schedulerManager;

  /**
   * Constructs a ConfigExistsConstraintValidator object.
   *
   * @param \Drupal\scheduler\SchedulerManager $scheduler_manager
   *   The Scheduler manager service.
   */
  public function __construct(SchedulerManager $scheduler_manager) {
    $this->schedulerManager = $scheduler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('scheduler.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {

    // If the content type is not enabled for unpublishing then exit early.
    if (!$this->schedulerManager->getThirdPartySetting($entity->getEntity(), 'unpublish_enable', FALSE)) {
      return;
    }

    $default_unpublish_required = $this->schedulerManager->setting('default_unpublish_required');
    $scheduler_unpublish_required = $this->schedulerManager->getThirdPartySetting($entity->getEntity(), 'unpublish_required', $default_unpublish_required);
    $publish_on = $entity->getEntity()->publish_on->value;
    $unpublish_on = $entity->value;
    $status = $entity->getEntity()->status->value;

    // When the 'required unpublishing' option is enabled the #required form
    // attribute cannot be set in every case. However a value must be entered if
    // also setting a publish-on date.
    if ($scheduler_unpublish_required && !empty($publish_on) && empty($unpublish_on)) {
      $this->context->buildViolation($constraint->messageUnpublishOnRequiredIfPublishOnEntered)
        ->atPath('unpublish_on')
        ->addViolation();
    }

    // Similar to the above scenario, the unpublish-on date must be entered if
    // the content is being published directly.
    if ($scheduler_unpublish_required && $status && empty($unpublish_on)) {
      $this->context->buildViolation($constraint->messageUnpublishOnRequiredIfPublishing)
        ->atPath('unpublish_on')
        ->addViolation();
    }

    // Check that the unpublish-on date is in the future. Unlike the publish-on
    // field, there is no option to use a past date, as this is not relevant for
    // unpublishing. The date must ALWAYS be in the future if it is entered.
    if ($unpublish_on && $unpublish_on < $this->schedulerManager->time->getRequestTime()) {
      $this->context->buildViolation($constraint->messageUnpublishOnDateNotInFuture)
        ->atPath('unpublish_on')
        ->addViolation();
    }

    // If both dates are entered then the unpublish-on date must be later than
    // the publish-on date.
    if (!empty($publish_on) && !empty($unpublish_on) && $unpublish_on < $publish_on) {
      $this->context->buildViolation($constraint->messageUnpublishOnTooEarly)
        ->atPath('unpublish_on')
        ->addViolation();
    }
  }

}
