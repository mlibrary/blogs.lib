<?php

namespace Drupal\openid_connect\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of OpenID Connect Clients.
 */
class OpenIDConnectClientListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() : array {
    $header = [
      'label' => [
        'data' => $this->t('OpenID Connect Client'),
        // 'field' => 'label',
        'specifier' => 'label',
      ],
      'type' => [
        'data' => $this->t('Type'),
      ],
      'status' => [
        'data' => $this->t('Status'),
        // 'field' => 'status',
        'specifier' => 'status',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) : array {
    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $entity */
    $plugin = $entity->getPlugin();

    $row['label'] = $entity->label();
    $row['type'] = $plugin->getLabel();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');

    return $row + parent::buildRow($entity);
  }

}
