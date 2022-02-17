<?php

namespace Drupal\og_menu\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\OgMenuInterface;

/**
 * Defines the OG Menu entity.
 *
 * @ConfigEntityType(
 *   id = "ogmenu",
 *   label = @Translation("OG Menu"),
 *   handlers = {
 *     "list_builder" = "Drupal\og_menu\OgMenuListBuilder",
 *     "form" = {
 *       "add" = "Drupal\og_menu\Form\OgMenuForm",
 *       "edit" = "Drupal\og_menu\Form\OgMenuForm",
 *       "delete" = "Drupal\og_menu\Form\OgMenuDeleteForm"
 *     }
 *   },
 *   config_prefix = "ogmenu",
 *   admin_permission = "administer og menu",
 *   bundle_of = "ogmenu_instance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/ogmenu/{ogmenu}",
 *     "overview-form" = "/admin/structure/ogmenu/{ogmenu}/overview",
 *     "edit-form" = "/admin/structure/ogmenu/{ogmenu}/edit",
 *     "delete-form" = "/admin/structure/ogmenu/{ogmenu}/delete",
 *     "collection" = "/admin/structure/visibility_group"
 *   }
 * )
 */
class OgMenu extends ConfigEntityBundleBase implements OgMenuInterface {

  /**
   * The OG Menu ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The OG Menu label.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
     // When the menu bundle is saved, link it to og.
    if (!$update && !$this->isSyncing()) {
      Og::createField(OgGroupAudienceHelperInterface::DEFAULT_FIELD, 'ogmenu_instance', $this->id());
    }
    // Invalidate the block cache to update menu-based derivatives.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();

    \Drupal::cache('menu')->invalidateAll();

    // Invalidate the block cache to update menu-based derivatives.
    if (\Drupal::moduleHandler()->moduleExists('block')) {
      \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
    }
  }

}
