<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'Unpublishing is enabled for the type of this entity' condition.
 *
 * SchedulerRulesConditionsTest provides test coverage.
 *
 * @Condition(
 *   id = "scheduler_unpublishing_is_enabled",
 *   deriver = "Drupal\scheduler_rules_integration\Plugin\Condition\ConditionDeriver"
 * )
 */
class UnpublishingIsEnabled extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * Config Factory service object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Determines whether scheduled unpublishing is enabled for this entity type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be checked.
   *
   * @return bool
   *   TRUE if scheduled unpublishing is enabled for the bundle of this entity
   *   type.
   */
  public function doEvaluate(EntityInterface $entity) {
    $default_unpublish_enable = $this->configFactory->get('scheduler.settings')->get('default_unpublish_enable');
    $bundle_field = $entity->getEntityType()->get('entity_keys')['bundle'];
    return ($entity->$bundle_field->entity->getThirdPartySetting('scheduler', 'unpublish_enable', $default_unpublish_enable));
  }

}
