<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide default OG Role selection handler.
 *
 * @EntityReferenceSelection(
 *   id = "og:og_role",
 *   label = @Translation("OG Role selection"),
 *   group = "og",
 *   weight = 0
 * )
 */
class OgRoleSelection extends DefaultSelection {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    ModuleHandlerInterface $module_handler,
    AccountInterface $current_user,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityRepositoryInterface $entity_repository,
    protected SelectionPluginManagerInterface $selectionManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $module_handler, $current_user, $entity_field_manager, $entity_type_bundle_info, $entity_repository);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity.repository'),
      $container->get('plugin.manager.entity_reference_selection'),
    );
  }

  /**
   * Get the selection handler of the field.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   *   Returns the selection handler.
   */
  public function getSelectionHandler(): SelectionInterface {
    $plugin = $this->selectionManager->getInstance(['target_type' => 'og_role']);
    assert($plugin instanceof SelectionInterface);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // @todo implement an easier, more consistent way to get the group type. At
    // the moment, this works either for checkboxes or OG Autocomplete widget
    // types on entities that have a getGroup() method. It also does not work
    // properly every time; for example during validation.
    $group = NULL;
    if (isset($this->configuration['entity'])) {
      $entity = $this->configuration['entity'];
      $group = is_callable([$entity, 'getGroup']) ? $entity->getGroup() : NULL;
    }

    if (isset($this->configuration['handler_settings']['group'])) {
      $group = $this->configuration['handler_settings']['group'];
    }

    if ($group === NULL) {
      return $query;
    }

    $query->condition('group_type', $group->getEntityTypeId(), '=');
    $query->condition('group_bundle', $group->bundle(), '=');
    $query->condition($query->orConditionGroup()
      ->condition('role_type', NULL, 'IS NULL')
      ->condition('role_type', 'required', '<>'));
    return $query;
  }

}
