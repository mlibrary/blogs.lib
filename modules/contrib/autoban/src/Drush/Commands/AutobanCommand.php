<?php

namespace Drupal\autoban\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\autoban\Controller\AutobanController;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush commandfile.
 */
final class AutobanCommand extends DrushCommands {

  /**
   * Constructs a Autoban Command object.
   */
  public function __construct(
    private readonly AutobanController $autoban,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('autoban'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'autoban:ban')]
  #[CLI\Argument(name: 'rule', description: 'Autoban rule id.')]
  #[CLI\Usage(name: 'autoban:ban', description: 'autoban:ban [rule ID]')]
  public function commandName($rule = NULL) {
    $autobanStorage = $this->entityTypeManager
      ->getStorage('autoban');
    $this->output()->writeln('Autoban banning start...');

    if (!empty($rule)) {
      // Checking the rule received from the user input.
      if (!$autobanStorage->load($rule)) {
        $this->logger()->error(sprintf('Wrong rule %s', $rule));
        return;
      }

      $this->output()->writeln(sprintf('Ban for rule %s', $rule));
      $banned = $this->banRule($rule);

      if ($banned > 0) {
        $this->logger()->success(sprintf('Banned count: %s', $banned));
      }
      else {
        $this->logger()->warning('No banned IP.');
      }
    }
    else {
      $rules = $autobanStorage->loadMultiple();
      $this->output()->writeln(sprintf('Ban for all rules: %s', count($rules)));
      if (!empty($rules)) {
        $totalBanned = 0;
        foreach (array_keys($rules) as $rule) {
          $banned = $this->banRule($rule);
          $this->logger()->notice(
            sprintf('Rule %s Banned count: %s', $rule, $banned)
          );
          $totalBanned += $banned;
        }

        if ($totalBanned > 0) {
          $this->logger()->success(sprintf('Total banned IP count: %s', $totalBanned));
        }
        else {
          $this->logger()->warning('No banned IP.');
        }
      }
    }

    $this->output()->writeln('Finished.');
  }

  /**
   * Rule ban.
   *
   * @param string $rule
   *   Autoban rule id.
   *
   * @return int
   *   Banned IP count.
   */
  private function banRule($rule) {
    $bannedIp = $this->autoban->getBannedIp($rule);
    $banned = 0;
    if ($bannedIp) {
      $banned = $this->autoban->banIpList($bannedIp, $rule);
    }
    return $banned;
  }

}
