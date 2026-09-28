<?php

namespace Drupal\link_attributes\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for link_attributes.
 */
class LinkAttributesHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.link_attributes':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The link attributes module provides a widget that allows users to add attributes to link fields. If you enable the Menu Link Content integration sub-module, it overtakes the core default widget for menu link content entities, allowing you to set attributes on menu links.') . '</p>';
        return $output;
    }
    return NULL;
  }

}
