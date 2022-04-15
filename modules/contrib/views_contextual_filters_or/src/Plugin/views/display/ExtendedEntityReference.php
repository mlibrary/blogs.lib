<?php

namespace Drupal\views_contextual_filters_or\Plugin\views\display;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\display\EntityReference;

/**
 * The plugin that handles an EntityReference display.
 *
 * @ingroup views_display_plugins
 */
class ExtendedEntityReference extends EntityReference {
  /**
   * Overrides \Drupal\views\Plugin\views\display\DisplayPluginBase::optionsSummary().
   */
  public function optionsSummary(&$categories, &$options) {
    DisplayPluginBase::optionsSummary($categories, $options);
    unset($options['title']);
  }
}
