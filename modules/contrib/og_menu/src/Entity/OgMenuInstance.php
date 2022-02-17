<?php

/**
 * @file
 * Contains \Drupal\og_menu\Entity\OgMenuInstance.
 */

namespace Drupal\og_menu\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\OgMenuInstanceInterface;

/**
 * Defines the OG Menu instance entity.
 *
 * @ingroup og_menu
 *
 * @ContentEntityType(
 *   id = "ogmenu_instance",
 *   label = @Translation("OG Menu instance"),
 *   bundle_label = @Translation("OG Menu"),
 *   handlers = {
 *     "views_data" = "Drupal\og_menu\Entity\OgMenuInstanceViewsData",
 *     "form" = {
 *       "default" = "Drupal\og_menu\Form\OgMenuInstanceForm",
 *       "add" = "Drupal\og_menu\Form\OgMenuInstanceForm",
 *       "edit" = "Drupal\og_menu\Form\OgMenuInstanceForm",
 *       "delete" = "Drupal\og_menu\Form\OgMenuInstanceDeleteForm",
 *     },
 *     "access" = "Drupal\og_menu\OgMenuInstanceAccessControlHandler",
 *   },
 *   base_table = "ogmenu_instance",
 *   admin_permission = "administer OgMenuInstance entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/ogmenu_instance/{ogmenu_instance}",
 *     "edit-form" = "/admin/ogmenu_instance/{ogmenu_instance}/edit",
 *     "delete-form" = "/admin/ogmenu_instance/{ogmenu_instance}/delete",
 *     "add-link" = "/admin/structure/ogmenu_instance/{ogmenu_instance}/add-link"
 *   },
 *   bundle_entity_type = "ogmenu",
 *   field_ui_base_route = "entity.ogmenu.edit_form"
 * )
 */
class OgMenuInstance extends ContentEntityBase implements OgMenuInstanceInterface {
  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the OG Menu instance entity.'))
      ->setReadOnly(TRUE);
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The OG Menu'))
      ->setSetting('target_type', 'ogmenu')
      ->setRequired(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the OG Menu instance entity.'))
      ->setReadOnly(TRUE);
    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the OG Menu instance entity.'))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 10,
      ));


    return $fields;
  }


  public function getDescription() {
  }

  /**
   * Determines if this menu is locked.
   *
   * @return bool
   *   TRUE if the menu is locked, FALSE otherwise.
   */
  public function isLocked() {
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      $menu_link_manager->deleteLinksInMenu('ogmenu-' . $entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    Cache::invalidateTags(['ogmenu_instance']);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $target_label = $this->getFieldTargetTypeLabel();
    // Use the label of the menu as the instance name.
    return $target_label ? $target_label : OgMenu::load($this->getType())
      ->label();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $field_storage = FieldStorageConfig::loadByName($this->getEntityTypeId(), OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    $target_type = $field_storage->getSetting('target_type');

    $value = $this->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->getValue();
    if (!$value || !$target_type) {
      throw new \Exception('No group has been associated with the OG Menu instance.');
    }
    /** @var \Drupal\Core\Entity\EntityInterface $target_entity */
    $target_entity = $this->entityTypeManager()
      ->getStorage($target_type)
      ->load($value[0]['target_id']);
    if (!$target_entity) {
      throw new \Exception('The group associated with the OG Menu instance could not be loaded.');
    }

    return $target_entity;
  }

  /**
   * Returns the label of the target group entity type of the instance.
   *
   * @return string
   *   The label of the entity type.
   */
  public function getFieldTargetTypeLabel() {
    try {
      return $this->getGroup()->label();
    }
    // If the group cannot be loaded, this is probably orphaned group content.
    catch (\Exception $e) {
      \Drupal::logger('og_menu')->log(RfcLogLevel::WARNING, 'Missing parent group for menu instance @id, check if OG is configured to delete orphans.', ['@id' => $this->id()]);
      return t('- Parent group missing -');
    }
  }

}
