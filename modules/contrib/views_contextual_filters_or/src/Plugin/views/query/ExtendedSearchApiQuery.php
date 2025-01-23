<?php

namespace Drupal\views_contextual_filters_or\Plugin\views\query;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\ViewExecutable;

/**
 * Object used to create a SELECT query.
 */
class ExtendedSearchApiQuery extends SearchApiQuery {

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['contextual_filters_or'] = [
      'default' => FALSE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['contextual_filters_or'] = [
      '#title' => t('Contextual filters OR'),
      '#description' => t('Contextual filters applied to OR logic.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['contextual_filters_or']),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function build(ViewExecutable $view) {
    if (!empty($this->where) && $this->options['contextual_filters_or']) {
      $where = [];
      foreach ($this->where as $group_id => $group) {
        if (empty($group_id) && (!empty($group['conditions']) || !empty($group['condition_groups']))) {
          $group += ['type' => 'OR'];
          if ($group_id === '') {
            $group_id = 0;
          }
        }
        $where[$group_id] = $group;
      }
      $this->where = $where;
    }
    parent::build($view);
  }

}
