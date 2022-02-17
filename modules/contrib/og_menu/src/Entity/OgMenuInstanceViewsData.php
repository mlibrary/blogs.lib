<?php

/**
 * @file
 * Contains \Drupal\og_menu\Entity\OgMenuInstance.
 */

namespace Drupal\og_menu\Entity;

use Drupal\views\EntityViewsData;
use Drupal\views\EntityViewsDataInterface;

/**
 * Provides Views data for OG Menu instance entities.
 */
class OgMenuInstanceViewsData extends EntityViewsData implements EntityViewsDataInterface {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['ogmenu_instance']['table']['base'] = array(
      'field' => 'id',
      'title' => $this->t('OG Menu instance'),
      'help' => $this->t('The OG Menu instance ID.'),
    );

    return $data;
  }

}
