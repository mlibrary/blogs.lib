<?php

namespace Drupal\scheduler\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * RuntimeRequirements.
 *
 * This is a replacement for hook_requirements in .install and is effective from
 * Drupal Core 11.3+ Earlier versions will still use the old function.
 */
class SchedulerRequirementsHook {

  use StringTranslationTrait;

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];

    // Report server internal clock.
    $requirements['scheduler_timecheck'] = [
      'title' => $this->t('Scheduler Time Check'),
      'value' => $this->t('Server time: %utc', _scheduler_timecheck()),
      'description' => [
        '#type' => 'inline_template',
        '#template' => '{{ description|raw }}',
        '#context' => ['description' => implode('<br />', _scheduler_timecheck('description'))],
      ],
    ];

    return $requirements;
  }

}
