<?php

declare(strict_types = 1);

namespace Drupal\symfony_mailer_lite;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Transports;

/**
 * Repository of mailer transport DSNs.
 */
final class TransportsFactory {

  /**
   * @var ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * @var EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new TransportsFactory.
   */
  public function __construct(ConfigFactoryInterface $configFactory, EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Get the collection of all transports.
   *
   * An object must always be returned, even if transport configuration is
   * invalid or missing transports.
   */
  public function create(): Transports {
    /** @var \Drupal\symfony_mailer_lite\Entity\Transport[] $transportConfigs */
    $transportConfigs = $this->entityTypeManager->getStorage('symfony_mailer_lite_transport')->loadMultiple();
    $transportConfigs = array_filter($transportConfigs, fn (Entity\Transport $transportConfig): bool => $transportConfig->status() === TRUE);
    $dsns = array_map(fn (Entity\Transport $transportConfig): string => $transportConfig->getDsn(), $transportConfigs);

    // The default transport must always be the first.
    // @see \Symfony\Component\Mailer\Transport\Transports::__construct
    $defaultTransportId = $this->configFactory->get('symfony_mailer_lite.settings')->get('default_transport');
    if (isset($dsns[$defaultTransportId])) {
      $defaultDsn = $dsns[$defaultTransportId];
      unset($dsns[$defaultTransportId]);
      // Unshift the default with key to the front of the DSN list.
      $dsns = [$defaultTransportId => $defaultDsn] + $dsns;
    }
    else {
      // If the default transport mapping no longer exists, unset everything
      // until a new default transport is created and/or nominated.
      $dsns = [];
    }

    // If nothing was configured, create a null transport.
    if (count($dsns) === 0) {
      $dsns = [
        'null' => 'null://null',
      ];
    }

    try {
      return Transport::fromDsns($dsns);
    }
    catch (\Exception $e) {
      // A Transports object with at least one transport must be returned.
      return Transport::fromDsns([
        'null://null',
      ]);
    }
  }

}
