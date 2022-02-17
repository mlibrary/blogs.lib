<?php

namespace Drupal\og_menu\Tests\Traits;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\Entity\OgMenuInstance;
use Drupal\og_menu\OgMenuInstanceInterface;

/**
 * Helper methods to use in OG Menu tests.
 */
trait OgMenuTrait {

  /**
   * Retrieves an OG Menu instance from the database.
   *
   * @param string $group_id
   *   The group id of the parent entity.
   * @param string $type
   *   The OG Menu bundle.
   *
   * @return \Drupal\og_menu\OgMenuInstanceInterface|null
   *    The menu instance as retrieved from the database, or NULL if no instance
   *    is found.
   */
  protected function getOgMenuInstance($group_id, $type) {
    $values = [
      'type' => $type,
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group_id,
    ];

    $instances = \Drupal::entityTypeManager()->getStorage('ogmenu_instance')->loadByProperties($values);

    return !empty($instances) ? array_pop($instances) : NULL;
  }

  /**
   * Created an OG Menu instance for a given group.
   *
   * @param string $group_id
   *    The id of the group that this menu will belong to.
   * @param string $type
   *   The OG Menu bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *    The newly created menu instance.
   *
   * @throws \Exception
   *    If the saving was unsuccessful.
   */
  protected function createOgMenuInstance($group_id, $type) {
    $values = [
      'type' => $type,
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $group_id,
    ];

    $og_menu_instance = OgMenuInstance::create($values);
    $og_menu_instance->save();
    if ($og_menu_instance->id()) {
      return $og_menu_instance;
    }
    throw new \Exception('Unable to save menu instance.');
  }

  /**
   * Creates a menu link.
   *
   * Used to create menu links for og menu instances.
   * The $item data is an array ready to be passed to the
   * MenuLinkContent::create method.
   *
   * @code
   *
   * $item_data = [
   *  'title' => 'My label for the menu',
   *  'link' => [
   *     'uri' => '/path/of/menu/item',
   *   ],
   *   'menu_name' => menu_machine_name,
   *   'weight' => 1,
   *   'expanded' => TRUE,
   * ];
   *
   * @end_code
   *
   * @param array $item_data
   *    The item data.
   *
   * @see \Drupal\menu_link_content\Entity\MenuLinkContent::create()
   */
  protected function createOgMenuItem(array $item_data) {
    $menu_link = MenuLinkContent::create($item_data);
    $menu_link->save();
  }

  /**
   * Returns the menu link tree for the given OG Menu instance.
   *
   * @param \Drupal\og_menu\OgMenuInstanceInterface $instance
   *   The OG Menu instance for which to return the menu tree.
   * @param int $min_depth
   *   Optional minimum depth for populating the tree. Defaults to 1.
   * @param int $max_depth
   *   Optional maximum depth for populating the tree. If omitted, the full
   *   depth will be used.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   A menu link tree.
   */
  protected function getOgMenuTree(OgMenuInstanceInterface $instance, $min_depth = 1, $max_depth = NULL) {
    /** @var \Drupal\Core\Cache\CacheBackendInterface $menu_cache_service */
    $menu_cache_service = \Drupal::service('cache.menu');
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $link_tree_service */
    $link_tree_service = \Drupal::service('menu.link_tree');

    // Load the menu link tree.
    $menu_name = 'ogmenu-' . $instance->id();
    $parameters = $link_tree_service->getCurrentRouteMenuTreeParameters($menu_name);
    $parameters->setMinDepth($min_depth);
    $parameters->setMaxDepth($max_depth);
    $parameters->expandedParents = [];

    // Clear the cache before loading it so we do not get outdated results. Sort
    // the 'conditions' so that it exactly matches the original cid.
    asort($parameters->conditions);
    $tree_cid = "tree-data:$menu_name:" . serialize($parameters);
    $menu_cache_service->delete($tree_cid);

    return $link_tree_service->load($menu_name, $parameters);
  }

}
