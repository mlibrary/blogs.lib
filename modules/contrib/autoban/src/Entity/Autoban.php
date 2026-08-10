<?php

namespace Drupal\autoban\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the autoban entity.
 *
 * The lines below, starting with '@ConfigEntityType,' are a plugin annotation.
 * These define the entity type to the entity type manager.
 *
 * The properties in the annotation are as follows:
 *  - id: The machine name of the entity type.
 *  - label: The human-readable label of the entity type. We pass this through
 *    the "@Translation" wrapper so that the multilingual system may
 *    translate it in the user interface.
 *  - handlers: An array of entity handler classes, keyed by handler type.
 *    - access: The class that is used for access checks.
 *    - list_builder: The class that provides listings of the entity.
 *    - form: An array of entity form classes keyed by their operation.
 *  - entity_keys: Specifies the class properties in which unique keys are
 *    stored for this entity type. Unique keys are properties which you know
 *    will be unique, and which the entity manager can use as unique in database
 *    queries.
 *  - links: entity URL definitions. These are mostly used for Field UI.
 *    Arbitrary keys can set here. For example, User sets cancel-form, while
 *    Node uses delete-form.
 *
 * @see Drupal\Core\Annotation\Translation
 *
 * @ingroup autoban
 *
 * @ConfigEntityType(
 *   id = "autoban",
 *   label = @Translation("Automatic IP ban"),
 *   admin_permission = "administer autoban",
 *   handlers = {
 *     "access" = "Drupal\autoban\AutobanAccessController",
 *     "list_builder" = "Drupal\autoban\Controller\AutobanListBuilder",
 *     "form" = {
 *       "add" = "Drupal\autoban\Form\AutobanAddForm",
 *       "edit" = "Drupal\autoban\Form\AutobanEditForm",
 *       "delete" = "Drupal\autoban\Form\AutobanDeleteForm"
 *     }
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/people/autoban/manage/{autoban}",
 *     "delete-form" = "/admin/config/people/autoban/manage/{autoban}/delete"
 *   },
 *   config_export = {
 *     "id",
 *     "type",
 *     "message",
 *     "referer",
 *     "threshold",
 *     "window",
 *     "user_type",
 *     "provider",
 *     "rule_type",
 *   },
 * )
 */
class Autoban extends ConfigEntityBase {

  /**
   * The autoban ID.
   *
   * @var string
   */
  public $id;

  /**
   * The autoban type.
   *
   * @var string
   */
  public $type;

  /**
   * The pattern of log message.
   *
   * @var string
   */
  public $message;

  /**
   * The pattern of log URL referrer.
   *
   * @var string
   */
  public $referer;

  /**
   * The threshold number of the log entries.
   *
   * @var int
   */
  public $threshold;

  /**
   * Relative date/time formatted window of log entries to check.
   *
   * @var string
   */
  public $window;

  /**
   * The IP ban provider.
   *
   * @var string
   */
  public $provider;

  /**
   * User type: anonymous (strict), authenticated (strict) or any.
   *
   * @var int
   */
  public $user_type;

  /**
   * Rule type: manual or authomatic.
   *
   * @var int
   */
  public $rule_type;

}
