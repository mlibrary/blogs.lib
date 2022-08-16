<?php

namespace Drupal\ckeditor_abbreviation\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\Entity\Editor;

/**
 * Provides an abbreviation dialog for text editors.
 */
class CkeditorAbbreviationDialog extends FormBase {
  /**
   * Gets the form's ID.
   *
   * @return string
   */
  public function getFormId() {
    return 'ckeditor_abbreviation_dialog';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form's state.
   * @param Drupal\editor\Entity\Editor $editor
   *   The editor.
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = isset($user_input['editor_object']) ? $user_input['editor_object'] : [];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="ckeditor-abbreviation-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['attributes']['text'] = [
      '#title' => $this->t('Abbreviation'),
      '#type' => 'textfield',
      '#default_value' => isset($input['text']) ? $input['text'] : '',
    ];
    $form['attributes']['title'] = [
      '#title' => $this->t('Explanation'),
      '#type' => 'textfield',
      '#default_value' => isset($input['title']) ? $input['title'] : '',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ]
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   *
   * @param array $form
   *   The form array.
   * @param Drupal\Core\Form\FormStateInterface $form_state
   *   The form's state.
   * @return Drupal\Core\Ajax\AjaxResponse
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);

      $form['status_message'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];

      $response->addCommand(new HtmlCommand('#ckeditor-abbreviation-dialog-form', $form));
    } else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }
}
