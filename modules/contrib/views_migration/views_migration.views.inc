<?php

/**
 * Implements hook_views_data().
 */
function views_migration_views_data() {
  $data['views']['migration_view'] = [
    'title' => t('Migration View area'),
    'help' => t('Insert a view inside an area.'),
    'area' => [
      'id' => 'migration_view',
    ],
  ];
  return $data;
}