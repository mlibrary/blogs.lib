<?php
// phpcs:ignoreFile
/**
 * @file
 * Drupal 10 and 11 compatibility layer.
 *
 * @deprecated in editor_advanced_link:2.2.8 and is removed from
 *     editor_advanced_link:2.4.0. There is no replacement.
 * @see https://www.drupal.org/project/drupal/issues/3239012
 */

namespace Drupal\editor_advanced_link\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface as Real;
use Drupal\Component\Plugin\PluginInspectionInterface;

if (interface_exists(Real::class)) {
  interface AdvancedLinkInterface extends Real { }
} else {
  interface AdvancedLinkInterface extends PluginInspectionInterface { }
}
