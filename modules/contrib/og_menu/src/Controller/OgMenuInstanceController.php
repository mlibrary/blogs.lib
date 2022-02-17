<?php

/**
 * @file
 * Contains Drupal\og_menu\Controller\OgMenuInstanceController.
 */

namespace Drupal\og_menu\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\MembershipManagerInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\Entity\OgMenu;
use Drupal\og_menu\Entity\OgMenuInstance;
use Drupal\og_menu\OgMenuInstanceInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class OgMenuInstanceController.
 *
 * @package Drupal\og_menu\Controller
 */
class OgMenuInstanceController extends ControllerBase {

  /**
   * The OG membership manager service.
   *
   * @var \Drupal\og\MembershipManagerInterface
   */
  protected $membershipManager;

  /**
   * Constructs an OgMenuInstanceController object.
   *
   * @param \Drupal\og\MembershipManagerInterface $membership_manager
   *   The OG membership manager service.
   */
  public function __construct(MembershipManagerInterface $membership_manager) {
    $this->membershipManager = $membership_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('og.membership_manager')
    );
  }

  /**
   * Controller for the create menu instance form.
   *
   * Depending on whether the menu instance already exists, the user will be
   * redirected to the entity create or edit form.
   *
   * @param \Drupal\og_menu\Entity\OgMenu $ogmenu
   *   The OG Menu that is associated with the menu instance.
   * @param \Drupal\Core\Entity\EntityInterface $og_group
   *   The group that is associated with the menu instance.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function createMenuInstance(OgMenu $ogmenu, EntityInterface $og_group) {
    $values = [
      'type' => $ogmenu->id(),
      OgGroupAudienceHelperInterface::DEFAULT_FIELD => $og_group->id(),
    ];
    // Menu exists, redirect to edit form.
    $instances = $this->entityTypeManager()->getStorage('ogmenu_instance')->loadByProperties($values);
    if ($instances) {
      $instance = array_pop($instances);
      return $this->redirect('entity.ogmenu_instance.edit_form', [
        'ogmenu_instance' => $instance->id(),
      ]);
    }

    // Create new menu instance.
    $entity = OgMenuInstance::create($values);
    $entity->save();
    if ($entity->id()) {
      return $this->redirect('entity.ogmenu_instance.edit_form', [
        'ogmenu_instance' => $entity->id(),
      ]);
    }
    throw new Exception('Unable to save menu instance.');
  }

  /**
   * Provides the menu link creation form.
   *
   * @param \Drupal\og_menu\OgMenuInstanceInterface $ogmenu_instance
   *   An entity representing a custom menu.
   *
   * @return array
   *   Returns the menu link creation form.
   */
  public function addLink(OgMenuInstanceInterface $ogmenu_instance) {
    $menu_link = $this->entityTypeManager()->getStorage('menu_link_content')->create(array(
      'id' => '',
      'parent' => '',
      'menu_name' => 'ogmenu-' . $ogmenu_instance->id(),
      'bundle' => 'menu_link_content',
    ));
    return $this->entityFormBuilder()->getForm($menu_link);
  }

  /**
   * Route title callback.
   *
   * @param \Drupal\og_menu\OgMenuInstanceInterface $ogmenu_instance
   *   The OG Menu instance that is being edited.
   *
   * @return array
   *   The title as a render array.
   */
  public function editFormTitle(OgMenuInstanceInterface $ogmenu_instance) {
    return ['#markup' => t('Edit menu %menu of %group', [
      '%menu' => $ogmenu_instance->bundle(),
      '%group' =>$ogmenu_instance->label()
    ]), '#allowed_tags' => Xss::getHtmlTagList()];
  }

  /**
   * Access callback for the "add link" route.
   *
   * @param \Drupal\og_menu\Entity\OgMenuInstance $ogmenu_instance
   *   The OG Menu instance for which to determine access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user for which to determine access.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function addLinkAccess(OgMenuInstance $ogmenu_instance, AccountInterface $account) {
    // @todo Add per-bundle permissions. You might want to give users access to
    //   add links to a particular OG Menu, but not all of them.
    $permission = 'add new links to og menu instance entities';

    // If the user has the global permission, allow access immediately.
    if ($account->hasPermission($permission)) {
      return AccessResult::allowed();
    }

    // Retrieve the associated group from the menu instance.
    $og_groups =$this->membershipManager->getGroups($ogmenu_instance);
    // A menu should only be associated with a single group.
    $group_entity_type = key($og_groups);
    $og_group = reset($og_groups[$group_entity_type]);

    // If the group could not be found, access could not be determined.
    if (empty($og_group)) {
      return AccessResult::neutral();
    }

    $membership = $this->membershipManager->getMembership($og_group, $account->id());
    // If the membership can not be found, access can not be determined.
    if (empty($membership)) {
      return AccessResult::neutral();
    }

    return AccessResult::allowedIf($membership->hasPermission($permission));
  }

}
