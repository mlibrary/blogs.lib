<?php

/**
 * @file
 * Contains \Drupal\og_menu\OgMenuInstanceInterface.
 */

namespace Drupal\og_menu;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining OG Menu instance entities.
 *
 * @ingroup og_menu
 */
interface OgMenuInstanceInterface extends ContentEntityInterface {

  /**
   * Gets the OG Menu.
   *
   * @return string
   *   The OG Menu.
   */
  public function getType();

  /**
   * Returns the group that is associated with the menu instance.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The group entity.
   *
   * @throws \Exception
   *   Thrown when no group is associated, or the group could not be retrieved.
   */
  public function getGroup();

}
