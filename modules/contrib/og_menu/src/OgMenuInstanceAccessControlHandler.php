<?php

/**
 * @file
 * Contains \Drupal\og_menu\OgMenuInstanceAccessControlHandler.
 */

namespace Drupal\og_menu;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OG Menu instance entity.
 *
 * @see \Drupal\og_menu\Entity\OgMenuInstance.
 */
class OgMenuInstanceAccessControlHandler extends EntityAccessControlHandler {
  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view og menu instance entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit og menu instance entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete og menu instance entities');

      case 'add-link':
        return AccessResult::allowedIfHasPermission($account, 'add new links to og menu instance entities');
    }

    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add og menu instance entities');
  }

}
