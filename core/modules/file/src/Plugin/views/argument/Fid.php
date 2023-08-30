<?php

namespace Drupal\file\Plugin\views\argument;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\argument\EntityArgument;

/**
 * Argument handler to accept multiple file ids.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("file_fid")
 */
class Fid extends EntityArgument implements ContainerFactoryPluginInterface {

}
