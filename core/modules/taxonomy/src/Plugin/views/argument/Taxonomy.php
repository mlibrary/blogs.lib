<?php

namespace Drupal\taxonomy\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler for basic taxonomy tid.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("taxonomy")
 */
class Taxonomy extends EntityArgument implements ContainerFactoryPluginInterface {

}
