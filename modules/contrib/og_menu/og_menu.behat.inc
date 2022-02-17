<?php

/**
 * @file
 * Contains \OgMenuSubContext.
 */

use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
use Drupal\og_menu\Tests\Traits\OgMenuTrait;

/**
 * Behat step definitions for testing OG Menus.
 */
class OgMenuSubContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  use OgMenuTrait;

  /**
   * Checks that the given OG Menu instance has the expected number of items.
   *
   * @param string $menu
   *   The name of the OG Menu instance to check.
   * @param string $label
   *   The group label.
   * @param string $type
   *   The group type, either 'collection' or 'solution'.
   * @param int $count
   *   The expected number of items in the menu.
   *
   * @Then the :menu menu of the :group group of type :type should have :count item(s)
   */
  public function assertMenuItemCount($menu, $label, $type, $count) {
    $group = $this->getEntityByLabel($type, $label);
    $menu_instance = $this->getOgMenuInstance($group->id(), $menu);
    $tree = $this->getOgMenuTree($menu_instance);

    PHPUnit_Framework_Assert::assertEquals($count, count($tree));
  }

  /**
   * Returns the entity with the given type, bundle and label.
   *
   * If multiple entities have the same label then the first one is returned.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param string $label
   *   The label to check.
   * @param string $bundle
   *   Optional bundle to check. If omitted, the entity can be of any bundle.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The requested entity.
   *
   * @throws \Exception
   *   Thrown when an entity with the given type, label and bundle does not
   *   exist.
   */
  protected function getEntityByLabel($entity_type, $label, $bundle = NULL) {
    $entity_manager = \Drupal::entityTypeManager();
    $storage = $entity_manager->getStorage($entity_type);
    $entity = $entity_manager->getDefinition($entity_type);

    $query = $storage->getQuery()
      ->condition($entity->getKey('label'), $label)
      ->range(0, 1);

    // Optionally filter by bundle.
    if ($bundle) {
      $query->condition($entity->getKey('bundle'), $bundle);
    }

    $result = $query->execute();

    if ($result) {
      $result = reset($result);
      return $storage->load($result);
    }

    throw new \Exception("The entity with label '$label' was not found.");
  }

}
