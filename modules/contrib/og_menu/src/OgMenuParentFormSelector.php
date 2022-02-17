<?php
namespace Drupal\og_menu;

use \Drupal\Core\Menu\MenuParentFormSelector;

class OgMenuParentFormSelector extends MenuParentFormSelector {
  protected $is_og_menu = FALSE;


  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL) {
    if (strpos($menu_parent, 'ogmenu-') !== FALSE) {
      $this->is_og_menu = TRUE;
    }
    return parent::parentSelectElement($menu_parent, $id, $menus);

  }

  protected function getMenuOptions(array $menu_names = NULL) {
    $entity_type = 'menu';
    if ($this->is_og_menu) {
      $entity_type = 'ogmenu_instance';
    }

    /**
     * @todo Here we'll need to do some access checks to see if which menus
     * belong to the og.
     */
    $menus = $this->entityTypeManager->getStorage($entity_type)->loadMultiple($menu_names);
    $options = array();
    /** @var \Drupal\system\MenuInterface[] $menus */
    foreach ($menus as $menu) {
      if ($this->is_og_menu) {
        $options['ogmenu-' . $menu->id()] = $menu->label();
      }
      else {
        $options[$menu->id()] = $menu->label();
      }

    }
    return $options;
  }

}
