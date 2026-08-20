<?php

namespace Drupal\autoban;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the autoban entity.
 *
 * We set this class to be the access controller in Autoban's entity annotation.
 *
 * @see \Drupal\autoban\Entity\Autoban
 *
 * @ingroup autoban
 */
class AutobanAccessController extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  public function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return AccessResult::allowedIfHasPermission($account, 'administer autoban');
  }

}
