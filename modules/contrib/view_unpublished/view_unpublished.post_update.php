<?php

/**
 * @file
 * Post update functions for View Unpublished.
 */

declare(strict_types=1);

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\node\NodeAccessRebuild;

/**
 * Flag node access for rebuild if other modules provide grant records.
 */
function view_unpublished_post_update_published_grant_other_modules(): void {
  $module_handler = \Drupal::moduleHandler();
  $other_modules = array_diff(array_keys($module_handler->getModuleList()), ['view_unpublished']);
  if (!$module_handler->hasImplementations('node_access_records', $other_modules)) {
    return;
  }
  DeprecationHelper::backwardsCompatibleCall(
    currentVersion: \Drupal::VERSION,
    deprecatedVersion: '11.4.0',
    currentCallable: fn () => \Drupal::service(NodeAccessRebuild::class)->setNeedsRebuild(),
    deprecatedCallable: fn () => node_access_needs_rebuild(TRUE),
  );
}
