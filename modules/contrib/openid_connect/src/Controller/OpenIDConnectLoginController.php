<?php

namespace Drupal\openid_connect\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Login controller.
 *
 * @package Drupal\openid_connect\Controller
 */
class OpenIDConnectLoginController extends ControllerBase {

  /**
   * Returns the login form.
   */
  public function loginForm() {
    return ['form' => $this->formBuilder()->getForm('Drupal\openid_connect\Form\OpenIDConnectLoginForm')];
  }

}
