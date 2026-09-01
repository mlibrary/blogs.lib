<?php

namespace Drupal\link_attributes_menu_link_content\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for link_attributes_menu_link_content.
 */
class LinkAttributesMenuLinkContentHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_entity_base_field_info_alter().
   */
  #[Hook('entity_base_field_info_alter')]
  public function entityBaseFieldInfoAlter(array &$fields, EntityTypeInterface $entity_type): void {
    if ($entity_type->id() === 'menu_link_content') {
      $fields['link']->setDisplayOptions('form', [
        'type' => 'link_attributes',
        'weight' => -2,
      ]);
    }
  }

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.link_attributes_menu_link_content':
        $output = '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The "link_attributes_menu_link_content" sub-module provides a widget that allows users to add attributes to menu links. It overtakes the core default widget for menu link content entities, allowing you to set attributes on menu links.') . '</p>';
        return $output;
    }
    return NULL;
  }

}
