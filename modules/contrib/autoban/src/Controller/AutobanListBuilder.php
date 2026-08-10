<?php

namespace Drupal\autoban\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of autoban entities.
 *
 * @package Drupal\autoban\Controller
 *
 * @ingroup autoban
 */
class AutobanListBuilder extends ConfigEntityListBuilder {

  use RedirectDestinationTrait;

  /**
   * Autoban provider list.
   *
   * @var array
   */
  private $banProviderList = NULL;

  /**
   * The autoban object.
   *
   * @var \Drupal\autoban\Controller\AutobanController
   */
  protected $autoban;

  /**
   * Construct the AutobanListBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\autoban\Controller\AutobanController $autoban
   *   Autoban object.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, AutobanController $autoban) {
    parent::__construct($entity_type, $storage);
    $this->autoban = $autoban;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('autoban')
    );
  }

  /**
   * Get ban providers list.
   *
   * @return array
   *   An array ban providers name.
   */
  private function getBanProvidersList() {
    $controller = $this->autoban;
    $providers = [];
    $banManagerList = $controller->getBanProvidersList();
    if (!empty($banManagerList)) {
      foreach ($banManagerList as $id => $item) {
        $providers[$id] = $item['name'];
      }
    }

    return $providers;
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['id'] = $this->t('Id');
    $header['type'] = $this->t('Type');
    $header['message'] = $this->t('Message pattern');
    $header['referer'] = $this->t('Referrer');
    $header['threshold'] = $this->t('Threshold');
    $header['window'] = $this->t('Window');
    $header['user_type'] = $this->t('User type');
    $header['provider'] = $this->t('Provider');

    return $header + parent::buildHeader();
  }

  /**
   * Builds a row for an entity in the entity listing.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the entity.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['type'] = $entity->type;
    $row['message'] = $entity->message;
    $row['referer'] = $entity->referer;
    $row['threshold'] = $entity->threshold;
    $row['window'] = $entity->window;

    $controller = $this->autoban;
    $row['user_type'] = $controller->userTypeList($entity->user_type ?: 0);

    if (!$this->banProviderList) {
      $this->banProviderList = $this->getBanProvidersList();
    }

    if (!empty($this->banProviderList) && isset($this->banProviderList[$entity->provider])) {
      $row['provider'] = $this->banProviderList[$entity->provider];
    }
    else {
      // If ban provider module is disabled.
      $row['provider'] = $this->t('Inactive provider %provider', ['%provider' => $entity->provider]);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * Operations list in the entity listing.
   *
   * @param Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to build the row.
   *
   * @return array
   *   A render array of the operations.
   */
  public function getOperations(EntityInterface $entity) {
    $operations = $this->getDefaultOperations($entity);

    $rule = $entity->id();
    $destination = $this->getDestinationArray();

    $operations['test'] = [
      'title' => $this->t('Test'),
      'url' => Url::fromRoute('autoban.test', ['rule' => $rule], ['query' => $destination]),
      'weight' => 20,
    ];
    $operations['ban'] = [
      'title' => $this->t('Ban'),
      'url' => Url::fromRoute('autoban.ban', ['rule' => $rule], ['query' => $destination]),
      'weight' => 30,
    ];
    $operations['clone'] = [
      'title' => $this->t('Clone'),
      'url' => Url::fromRoute('entity.autoban.add_form', ['rule' => $rule], ['query' => $destination]),
      'weight' => 40,
    ];

    uasort($operations, '\\Drupal\\Component\\Utility\\SortArray::sortByWeightElement');
    return $operations;
  }

}
