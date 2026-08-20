<?php

declare(strict_types=1);

namespace Drupal\autoban\EventSubscriber;

use Drupal\autoban\Controller\AutobanController;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autoban event subscriber.
 */
final class AutobanSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an AutobanSubscriber object.
   */
  public function __construct(
    private readonly AutobanController $autoban,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::EXCEPTION => ['onKernelException'],
    ];
  }

  /**
   * Ban on Kernel exception.
   */
  public function onKernelException(ExceptionEvent $event) {
    $throwable = $event->getThrowable();
    if ($throwable instanceof NotFoundHttpException
    || $throwable instanceof AccessDeniedHttpException) {
      $this->ban($event->getRequest());
    }
  }

  /**
   * Ban the IP.
   */
  protected function ban(Request $request) {
    $config = $this->configFactory->get('autoban.settings');
    $force_mode = $config->get('autoban_force_mode') ?? FALSE;
    if (!$force_mode) {
      return;
    }

    $rules = $this->entityTypeManager->getStorage('autoban')->loadMultiple();
    if (empty($rules)) {
      return;
    }

    foreach (array_keys($rules) as $rule) {
      $banned_ip = $this->autoban->getBannedIp($rule);
      $this->autoban->banIpList($banned_ip, $rule);
    }
  }

}
