<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;

/**
 * Provides a confirm form for deleting the OpenID Connect client entity.
 */
class OpenIDConnectClientDeleteForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() : TranslatableMarkup {
    return $this->t('Are you sure you want to delete OpenID Connect client %label?', ['%label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() : TranslatableMarkup {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() : Url {
    return new Url('entity.openid_connect_client.list');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $label = $this->entity->label();

    try {
      // Delete the entity.
      $this->entity->delete();

      $this->messenger()->addMessage($this->t('OpenID Connect client %label was deleted.', ['%label' => $label]));
      $form_state->setRedirectUrl($this->getCancelUrl());
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addMessage($this->t('Error when trying to delete OpenID Connect client %label.', ['%label' => $label]));
    }
  }

}
