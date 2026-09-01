<?php

namespace Drupal\link_attributes_test_alterinfo\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hook implementations for link_attributes_test_alterinfo.
 */
class LinkAttributesTestAlterinfoHooks {

  public function __construct(
    private StateInterface $state,
  ) {
  }

  /**
   * Implements hook_link_attributes_plugin_alter().
   */
  #[Hook('link_attributes_plugin_alter')]
  public function linkAttributesPluginAlter(array &$definitions): void {
    $id = 'link_attributes_test_alterinfo.hook_link_attributes_plugin_alter';
    // Alter only if our state flag is set.
    switch ($this->state->get($id)) {
      case 'type_one':
        self::alterTypeOne($definitions);
        break;

      case 'type_two':
        self::alterTypeTwo($definitions);
        break;
    }
  }

  /**
   * Link alter for testing type one.
   */
  protected static function alterTypeOne(array &$definitions): void {
    $definitions['class']['title'] = t('Link style');
    $definitions['class']['description'] = t('Select how the link should be displayed.');
    $definitions['class']['type'] = 'select';
    $definitions['class']['options'] = [
      'link' => 'Link',
      'button' => 'Button',
      'Group' => [
        'grouped' => 'Group',
      ],
    ];
    $definitions['class']['default_value'] = 'button';
    $definitions['target']['default_value'] = '_blank';
  }

  /**
   * Link alter for testing type two.
   */
  protected static function alterTypeTwo(array &$definitions): void {
    $definitions['class']['required'] = TRUE;
  }

}
