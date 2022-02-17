<?php
/**
 * @file
 * Contains \OgMenuServiceProvider
 */

namespace Drupal\og_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
/**
 * Modifies the language manager service.
 */
class OgMenuServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('menu.parent_form_selector');
    $definition->setClass('Drupal\og_menu\OgMenuParentFormSelector');
  }
}