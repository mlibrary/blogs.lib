<?php

namespace Drupal\views_contextual_filters_or\Plugin\views\query;

use Drupal\views\Plugin\views\query\Sql;
use Drupal\Core\Form\FormStateInterface;

/**
 * Object used to create a SELECT query.
 */
class ExtendedSql extends Sql {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['contextual_filters_or'] = array(
      'default' => FALSE,
    );

    return $options;
  }

  /**
   * Add settings for the ui.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['contextual_filters_or'] = array(
      '#title' => t('Contextual filters OR'),
      '#description' => t('Contextual filters applied to OR logic.'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['contextual_filters_or']),
    );
  }

  /**
   * Add a simple WHERE clause to the query. The caller is responsible for
   * ensuring that all fields are fully qualified (TABLE.FIELD) and that
   * the table already exists in the query.
   *
   * The $field, $value and $operator arguments can also be passed in with a
   * single DatabaseCondition object, like this:
   * @code
   * $this->query->addWhere(
   *   $this->options['group'],
   *   db_or()
   *     ->condition($field, $value, 'NOT IN')
   *     ->condition($field, $value, 'IS NULL')
   * );
   * @endcode
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more
   *   complex options such as IN, LIKE, LIKE BINARY, or BETWEEN. Defaults to =.
   *   If $field is a string you have to use 'formula' here.
   *
   * @see \Drupal\Core\Database\Query\ConditionInterface::condition()
   * @see \Drupal\Core\Database\Query\Condition
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $op = $this->options['contextual_filters_or'] ? 'OR' : 'AND';
      $this->setWhereGroup($op, $group);
    }

    $this->where[$group]['conditions'][] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );
  }

  /**
   * Add a complex WHERE clause to the query.
   *
   * The caller is responsible for ensuring that all fields are fully qualified
   * (TABLE.FIELD) and that the table already exists in the query.
   * Internally the dbtng method "where" is used.
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $snippet
   *   The snippet to check. This can be either a column or
   *   a complex expression like "UPPER(table.field) = 'value'"
   * @param $args
   *   An associative array of arguments.
   *
   * @see QueryConditionInterface::where()
   */
  public function addWhereExpression($group, $snippet, $args = array()) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $op = $this->options['contextual_filters_or'] ? 'OR' : 'AND';
      $this->setWhereGroup($op, $group);
    }

    $this->where[$group]['conditions'][] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => 'formula',
    );
  }

  /**
   * Add a complex HAVING clause to the query.
   * The caller is responsible for ensuring that all fields are fully qualified
   * (TABLE.FIELD) and that the table and an appropriate GROUP BY already exist in the query.
   * Internally the dbtng method "having" is used.
   *
   * @param $group
   *   The HAVING group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $snippet
   *   The snippet to check. This can be either a column or
   *   a complex expression like "COUNT(table.field) > 3"
   * @param $args
   *   An associative array of arguments.
   *
   * @see QueryConditionInterface::having()
   */
  public function addHavingExpression($group, $snippet, $args = array()) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->having[$group])) {
      $op = $this->options['contextual_filters_or'] ? 'OR' : 'AND';
      $this->setWhereGroup($op, $group, 'having');
    }

    // Add the clause and the args.
    $this->having[$group]['conditions'][] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => 'formula',
    );
  }
}
