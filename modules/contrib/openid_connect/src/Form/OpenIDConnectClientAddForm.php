<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the add form for the OpenID Connect client entity.
 */
class OpenIDConnectClientAddForm extends OpenIDConnectClientFormBase {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Create OpenID Connect client');
    return $actions;
  }

}
