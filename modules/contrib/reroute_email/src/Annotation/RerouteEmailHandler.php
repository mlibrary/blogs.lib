<?php

namespace Drupal\reroute_email\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a RerouteEmailHandler item annotation object.
 *
 * @Annotation
 */
class RerouteEmailHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public string $id;


  /**
   * Where the plugin is automatically run.
   *
   * Allowed values:
   *  - hook_mail_alter;
   *  - symfony_mailer_adjuster.
   *
   * @var array
   */
  public array $applied;

}
