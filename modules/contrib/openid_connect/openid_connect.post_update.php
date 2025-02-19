<?php

/**
 * @file
 * Post update functions for the OpenID Connect module.
 */

declare(strict_types=1);

/**
 * Rebuild the container to ensure that the cache is cleared.
 */
function openid_connect_post_update_rebuild_container_3462532(): void {
  // Leaving empty will trigger a cache rebuild.
  // @see https://www.drupal.org/project/openid_connect/issues/3462532
}
