<?php

namespace Drupal\force_users_logout\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */

  protected $database;

  /**
   * Constructs.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection) {
    $this->database = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Returns response for the autocompletion.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object containing the search string.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing the autocomplete suggestions.
   */
  public function handleAutocomplete(Request $request) {
    $matches = [];
    $string = $request->query->get('q');
    if ($string) {
      $matches = [];
      $query = $this->entityTypeManager()->getStorage('user')->getQuery()
        ->condition('status', 1)
        ->condition('name', '%' . $this->database->escapeLike($string) . '%', 'LIKE');
      $uids = $query->accessCheck()->execute();
      $result = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple($uids);
      foreach ($result as $row) {
        $matches[] = [
          'value' => $row->name->value . ' (' . $row->uid->value . ')',
          'label' => $row->name->value,
        ];
      }
    }
    return new JsonResponse($matches);
  }

}
