<?php

namespace Drupal\twig_vardumper\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for twig_vardumper.
 */
class TwigVardumperHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the twig_vardumper module.
      case 'help.page.twig_vardumper':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Twig vardumper provides a better {{ dump() }} and {{ vardumper() }} function that can help you debug Twig variables.') . '</p>';
        return $output;

      default:
    }
  }

}
