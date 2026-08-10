<?php

namespace Drupal\autoban\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class Autoban Delete Form.
 *
 * @package Drupal\autoban\Form
 *
 * @ingroup autoban
 */
class AutobanDeleteForm extends EntityConfirmFormBase {

  /**
   * Gathers a confirmation question.
   *
   * @return string
   *   Translated string.
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete autoban rule %label?', [
      '%label' => $this->entity->id(),
    ]);
  }

  /**
   * Gather the confirmation text.
   *
   * @return string
   *   Translated string.
   */
  public function getConfirmText() {
    return $this->t('Delete rule');
  }

  /**
   * Gets the cancel URL.
   *
   * @return \Drupal\Core\Url
   *   The URL to go to if the user cancels the deletion.
   */
  public function getCancelUrl() {
    return new Url('entity.autoban.list');
  }

  /**
   * The submit handler for the confirm form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the entity.
    $this->entity->delete();

    // Set a message that the entity was deleted.
    $this->messenger()->addMessage($this->t('Autoban rule #%label was deleted.', [
      '%label' => $this->entity->id(),
    ]));

    // Redirect the user to the list controller when complete.
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
