<?php

/**
 * @file
 * Contains \Drupal\og_menu\Controller\OgMenuController.
 */

namespace Drupal\og_menu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\og_menu\OgMenuInterface;

/**
 * Defines a route controller for a form for menu link content entity creation.
 */
class OgMenuController extends ControllerBase {
  /**
   * @param \Drupal\og_menu\OgMenuInterface $ogmenu
   *   The og menu object.
   * @return array
   *   The page title render array.
   */
  public static function title(OgMenuInterface $ogmenu) {
    return ['#markup' => $ogmenu->label(), '#allowed_tags' => Xss::getHtmlTagList()];
  }

}
