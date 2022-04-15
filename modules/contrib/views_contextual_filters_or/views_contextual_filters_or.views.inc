<?php

/**
 * @file
 * Primarily Drupal hooks to manipulate Views Contextual Filters OR.
 *
 * This is the main module file for Views Contextual Filters OR.
 */

/**
 * Implements hook_views_plugins_query_alter().
 */
function views_contextual_filters_or_views_plugins_query_alter(array &$plugins) {
  if (!empty($plugins['views_query'])) {
    $plugins['views_query']['class'] = 'Drupal\views_contextual_filters_or\Plugin\views\query\ExtendedSql';
  }

  if (!empty($plugins['search_api_query'])) {
    $plugins['search_api_query']['class'] = 'Drupal\views_contextual_filters_or\Plugin\views\query\ExtendedSearchApiQuery';
  }
}

/**
 * Implements hook_views_plugins_display_alter().
 */
function views_contextual_filters_or_views_plugins_display_alter(array &$plugins) {
  if (!empty($plugins['entity_reference'])) {
    $plugins['entity_reference']['class'] = 'Drupal\views_contextual_filters_or\Plugin\views\display\ExtendedEntityReference';
  }
}
