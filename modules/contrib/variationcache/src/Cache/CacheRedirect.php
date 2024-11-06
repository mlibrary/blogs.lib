<?php

namespace Drupal\variationcache\Cache;

/**
 * @file
 * Contains a class alias to keep old code functioning now that this module has
 * been integrated into Drupal 10.2 and higher. If you are running on this core
 * version, you should simply uninstall this module and update code that used to
 * use this module to point to the core classes directly.
 *
 * This is an extra precaution on top of the class_alias calls in the module
 * file because, sometimes, the variation_cache_factory service is already
 * instantiated while the container is being built (e.g. in an event subscriber)
 * and the module file hasn't been loaded yet at that point.
 */
if (!class_exists('\Drupal\Core\Cache\CacheRedirect')) {
  @class_alias('\Drupal\variationcache\Old\Cache\CacheRedirect', '\Drupal\variationcache\Cache\CacheRedirect');
}
else {
  @class_alias('\Drupal\Core\Cache\CacheRedirect', '\Drupal\variationcache\Cache\CacheRedirect');
}
