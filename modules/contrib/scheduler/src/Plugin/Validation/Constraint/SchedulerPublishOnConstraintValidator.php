<?php

namespace Drupal\scheduler\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\scheduler\SchedulerManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the SchedulerPublishOn constraint.
 *
 * SchedulerPastDatesTest provides test coverage.
 */
class SchedulerPublishOnConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

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

    // If the content type is not enabled for publishing then exit early.
    if (!$this->schedulerManager->getThirdPartySetting($entity->getEntity(), 'publish_enable', FALSE)) {
      return;
    }

    $publish_on = $entity->value;
    $default_publish_past_date = $this->schedulerManager->setting('default_publish_past_date');
    $scheduler_publish_past_date = $this->schedulerManager->getThirdPartySetting($entity->getEntity(), 'publish_past_date', $default_publish_past_date);

    if ($publish_on && $scheduler_publish_past_date == 'error' && $publish_on < $this->schedulerManager->time->getRequestTime()) {
      $this->context->buildViolation($constraint->messagePublishOnDateNotInFuture)
        ->atPath('publish_on')
        ->addViolation();
    }
  }

}
