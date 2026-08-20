<?php

namespace Drupal\autoban_advban;

use Drupal\Core\Database\Connection;
use Drupal\advban\AdvbanIpManager;
use Drupal\autoban\AutobanProviderInterface;

/**
 * IP manager class for Advanced Ban (range) module.
 */
class AdvbanRangeProvider implements AutobanProviderInterface {

  /**
   * Constructs a Advban Range Provider object.
   */
  public function __construct(
    private readonly AdvbanIpManager $banIpManager,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return 'advban_range';
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'Advanced Ban (range)';
  }

  /**
   * {@inheritdoc}
   */
  public function getBanType() {
    return 'range';
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
