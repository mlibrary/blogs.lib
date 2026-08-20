<?php

namespace Drupal\autoban_advban;

use Drupal\Core\Database\Connection;
use Drupal\advban\AdvbanIpManager;
use Drupal\autoban\AutobanProviderInterface;

/**
 * IP manager class for core Ban module.
 */
class AdvbanProvider implements AutobanProviderInterface {

  /**
   * Constructs a Advban Provider object.
   */
  public function __construct(
    private readonly AdvbanIpManager $banIpManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'advban';
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Advanced Ban';
  }

  /**
   * {@inheritdoc}
   */
  public function getBanType() {
    return 'single';
  }

  /**
   * {@inheritdoc}
   */
  public function hasMetadata() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getBanIpManager(Connection $connection) {
    return $this->banIpManager;
  }

}
