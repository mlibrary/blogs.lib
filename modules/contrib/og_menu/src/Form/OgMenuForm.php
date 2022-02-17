<?php

namespace Drupal\og_menu\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OgMenuForm.
 */
class OgMenuForm extends BundleEntityFormBase {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ogmenu = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ogmenu->label(),
      '#description' => $this->t("Label for the OG Menu."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $ogmenu->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\og_menu\Entity\OgMenu::load',
      ),
      '#disabled' => !$ogmenu->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ogmenu = $this->entity;
    $status = $ogmenu->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label OG Menu.', [
          '%label' => $ogmenu->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label OG Menu.', [
          '%label' => $ogmenu->label(),
        ]));
    }
    $form_state->setRedirectUrl($ogmenu->toUrl('collection'));
  }

}
