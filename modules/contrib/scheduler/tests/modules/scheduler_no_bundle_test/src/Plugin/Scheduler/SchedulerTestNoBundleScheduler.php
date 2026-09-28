<?php

namespace Drupal\scheduler_no_bundle_test\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Plugin for scheduler_test_no_bundle entity type.
 *
 * @SchedulerPlugin(
 *  id = "scheduler_test_no_bundle_scheduler",
 *  label = @Translation("Scheduler test no bundle entity scheduler plugin"),
 *  description = @Translation("Provides scheduler support for a no-bundle entity type"),
 *  entityType = "scheduler_test_no_bundle",
 *  dependency = "scheduler_no_bundle_test"
 * )
 */
class SchedulerTestNoBundleScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {}
