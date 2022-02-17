<?php

namespace Drupal\og_menu;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of OG Menu entities.
 */
class OgMenuListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('OG Menu');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations =  parent::getDefaultOperations($entity);
    if ($entity->access('view') && $entity->hasLinkTemplate('overview-form')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => 0,
        'url' => $entity->toUrl('overview-form'),
      );
    }
    return $operations;
  }

}
