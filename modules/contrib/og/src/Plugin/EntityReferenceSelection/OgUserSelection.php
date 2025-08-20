<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\EntityReferenceSelection;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provide OG User selection handler for memberships.
 *
 * @EntityReferenceSelection(
 *   id = "og:user",
 *   label = @Translation("OG Membership user selection"),
 *   group = "og",
 *   entity_types = {"user"},
 *   weight = 0
 * )
 */
class OgUserSelection extends DefaultSelection {

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
    protected MembershipManagerInterface $membershipManager,
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
      $container->get('og.membership_manager'),
      $container->get('plugin.manager.entity_reference_selection'),
    );
  }

  /**
   * Get the selection handler of the field.
   *
   * @return \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
   *   Returns the selection handler.
   */
  public function getSelectionHandler() {
    $plugin = $this->selectionManager->getInstance(['target_type' => 'user']);
    assert($plugin instanceof SelectionInterface);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Anon can't be a group member.
    $query->condition('uid', 0, '<>');

    // The user entity doesn't have a label column.
    if (isset($match)) {
      $query->condition('name', $match, $match_operator);
    }

    // Adding the permission check is sadly insufficient for users: core
    // requires us to also know about the concept of 'blocked' and 'active'.
    if (!$this->currentUser->hasPermission('administer users')) {
      $query->condition('status', 1);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) {
    // Exclude users who are already in the current group.
    // This has to be done on the SQL query rather than the entity query,
    // because a reverse relationship to the OG membership entity is needed.
    // @todo implement an easier, more consistent way to get the group type. At
    // the moment, this works either for checkboxes or OG Autocomplete widget
    // types on entities that have a getGroup() method. It also does not work
    // properly every time; for example during validation.
    $group = NULL;
    if (isset($this->configuration['entity'])) {
      $entity = $this->configuration['entity'];
      $group = is_callable([$entity, 'getGroup']) ? $entity->getGroup() : NULL;
    }

    if (isset($this->configuration['group'])) {
      $group = $this->configuration['group'];
    }

    if (!$group) {
      return;
    }

    // Left join to the OG membership base table.
    $query->leftJoin('og_membership', 'ogm', 'base_table.uid = ogm.uid AND ogm.entity_type = :entity_type AND ogm.entity_id = :entity_id', [
      ':entity_type' => $group->getEntityTypeId(),
      ':entity_id' => $group->id(),
    ]);

    // Exclude any users who are in the current group.
    $query->isNull('ogm.id');
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $user = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable user, it needs to be active.
    if (!$this->currentUser->hasPermission('administer users')) {
      /** @var \Drupal\user\UserInterface $user */
      $user->activate();
    }

    return $user;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('administer users')) {
      $entities = array_filter($entities, function ($user) {
        /** @var \Drupal\user\UserInterface $user */
        return $user->isActive();
      });
    }
    return $entities;
  }

}
