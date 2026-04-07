<?php

namespace Drupal\scheduler_rules_integration\Plugin\Condition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides 'Publishing is enabled for the type of this entity' condition.
 *
 * SchedulerRulesConditionsTest provides test coverage.
 *
 * @Condition(
 *   id = "scheduler_publishing_is_enabled",
 *   deriver = "Drupal\scheduler_rules_integration\Plugin\Condition\ConditionDeriver"
 * )
 */
class PublishingIsEnabled extends RulesConditionBase implements ContainerFactoryPluginInterface {

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
   * Determines whether scheduled publishing is enabled for this entity type.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be checked.
   *
   * @return bool
   *   TRUE if scheduled publishing is enabled for the bundle of this entity
   *   type.
   */
  public function doEvaluate(EntityInterface $entity) {
    $default_publish_enable = $this->configFactory->get('scheduler.settings')->get('default_publish_enable');
    $bundle_field = $entity->getEntityType()->get('entity_keys')['bundle'];
    return ($entity->$bundle_field->entity->getThirdPartySetting('scheduler', 'publish_enable', $default_publish_enable));
  }

}
