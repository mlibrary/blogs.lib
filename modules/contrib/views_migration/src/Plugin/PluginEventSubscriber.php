<?php

namespace Drupal\views_migration\Plugin;

use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to forward Migrate events to source and destination plugins.
 */
class PluginEventSubscriber implements EventSubscriberInterface {

  /**
   * Forwards post-import events to the source and destination plugins.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The import event.
   */
  public function postImport(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    $id = $migration->id();
    $status = $migration->getStatus();
    if($id == 'd7_views_migration' && $status == 1) {
      $connection = \Drupal::database();
      $query = $connection->select('migrate_map_d7_views_migration', 'mmvm');
      $query->addField('mmvm', 'destid1');
      $result = $query->execute();
      $view_ids = $result->fetchCol();
      $viewStorage = \Drupal::entityTypeManager()->getStorage('view');
      foreach ($view_ids as $key => $view_id) {
        $config = \Drupal::service('config.factory')->getEditable('views.view.'.$view_id);
        $displays = $config->get('display');
        foreach ($displays as &$display) {
          if(isset($display['display_options']['header'])){
            foreach ($display['display_options']['header'] as &$field_value) {
              if(isset($field_value['plugin_id']) && $field_value['plugin_id'] == 'migration_view'){
                $field_value['plugin_id'] = 'view';
                $field_value['field'] = 'view';
              }
            } 
          }
        }
        $config->set('display',$displays);
        $config->save();
        $view = $viewStorage->load($view_id);
        $view->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[MigrateEvents::POST_IMPORT][] = ['postImport'];
    return $events;
  }

}
