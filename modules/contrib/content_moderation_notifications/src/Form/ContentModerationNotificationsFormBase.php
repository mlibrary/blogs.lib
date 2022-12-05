<?php

namespace Drupal\content_moderation_notifications\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class ContentModerationNotificationFormBase.
 *
 * Typically, we need to build the same form for both adding a new entity,
 * and editing an existing entity. Instead of duplicating our form code,
 * we create a base class. Drupal never routes to this class directly,
 * but instead through the child classes of ContentModerationNotificationAddForm
 * and ContentModerationNotificationEditForm.
 *
 * @package Drupal\content_moderation_notifications\Form
 *
 * @ingroup content_moderation_notifications
 */
class ContentModerationNotificationsFormBase extends EntityForm {

  /**
   * Update options.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate.
   *
   * @return mixed
   *   Returns the updated options.
   */
  public static function updateWorkflowTransitions(array $form, FormStateInterface &$form_state) {
    return $form['transitions_wrapper'];
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   *
   * Builds the entity add/edit form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An associative array containing the content_moderation_notification
   *   add/edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve a list of all possible workflows.
    /** @var \Drupal\workflows\WorkflowInterface[] $workflows */
    $workflows = $this->entityTypeManager->getStorage('workflow')->loadMultiple();

    // Return early if there are no available workflows.
    if (empty($workflows)) {
      $form['no_workflows'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No workflows available. <a href=":url">Manage workflows</a>.', [':url' => Url::fromRoute('entity.workflow.collection')->toString()]),
      ];
      return $form;
    }

    // Get anything we need from the base class.
    $form = parent::buildForm($form, $form_state);

    // Drupal provides the entity to us as a class variable. If this is an
    // existing entity, it will be populated with existing values as class
    // variables. If this is a new entity, it will be a new object with the
    // class of our entity. Drupal knows which class to call from the
    // annotation on our ContentModerationNotification class.
    /** @var \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $content_moderation_notification */
    $content_moderation_notification = $this->entity;

    // Build the options array of workflows.
    $workflow_options = [];
    foreach ($workflows as $workflow_id => $workflow) {
      $workflow_options[$workflow_id] = $workflow->label();
    }

    // Default to the first workflow in the list.
    $workflow_keys = array_keys($workflow_options);

    if ($form_state->getValue('workflow')) {
      $selected_workflow = $form_state->getValue('workflow');
    }
    elseif (isset($content_moderation_notification->workflow)) {
      $selected_workflow = $content_moderation_notification->workflow;
    }
    else {
      $selected_workflow = array_shift($workflow_keys);
    }

    $form['label'] = [
      '#title' => $this->t('Label'),
      '#type' => 'textfield',
      '#default_value' => $content_moderation_notification->label(),
      '#description' => $this->t('The label for this notification.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $content_moderation_notification->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'source' => ['label'],
      ],
      '#disabled' => !$content_moderation_notification->isNew(),
    ];

    // Allow the workflow to be selected, this will dynamically update the
    // available transition lists.
    $form['workflow'] = [
      '#type' => 'select',
      '#title' => $this->t('Workflow'),
      '#options' => $workflow_options,
      '#default_value' => $selected_workflow,
      '#required' => TRUE,
      '#description' => $this->t('Select a workflow'),
      '#ajax' => [
        'wrapper' => 'workflow_transitions_wrapper',
        'callback' => static::class . '::updateWorkflowTransitions',
      ],
    ];

    // Ajax replaceable fieldset.
    $form['transitions_wrapper'] = [
      '#type' => 'container',
      '#prefix' => '<div id="workflow_transitions_wrapper">',
      '#suffix' => '</div>',
    ];

    // Transitions.
    $state_transitions_options = [];
    $state_transitions = $workflows[$selected_workflow]->getTypePlugin()->getTransitions();
    foreach ($state_transitions as $key => $transition) {
      $state_transitions_options[$key] = $transition->label();
    }

    $form['transitions_wrapper']['transitions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Transitions'),
      '#options' => $state_transitions_options,
      '#default_value' => isset($content_moderation_notification->transitions) ? $content_moderation_notification->transitions : [],
      '#required' => TRUE,
      '#description' => $this->t('Select which transitions triggers this notification.'),
    ];

    // Role selection.
    $roles_options = user_role_names(TRUE);

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => $roles_options,
      '#default_value' => $content_moderation_notification->getRoleIds(),
      '#description' => $this->t('Send notifications to all users with these roles.'),
    ];

    // Send email to author?
    $form['author'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Email the author?'),
      '#default_value' => $content_moderation_notification->sendToAuthor(),
      '#description' => $this->t('Send notifications to the current author of the content.'),
    ];
    $form['site_mail'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable the site email address'),
      '#default_value' => $content_moderation_notification->disableSiteMail(),
      '#description' => $this->t('Do not send notifications to the site email address.'),
    ];

    $form['emails'] = [
      '#type' => 'textarea',
      '#rows' => 3,
      '#title' => $this->t('Adhoc email addresses'),
      '#default_value' => $content_moderation_notification->getEmails(),
      '#description' => $this->t('Send notifications to these email addresses. Separate emails with commas or newlines. You may use Twig templating code in this field.'),
    ];

    // Email subject line.
    $form['subject'] = [
      '#type' => 'textarea',
      '#rows' => 1,
      '#title' => $this->t('Email Subject'),
      '#default_value' => $content_moderation_notification->getSubject(),
      '#required' => TRUE,
      '#description' => $this->t('You may use Twig templating code in this field.'),
    ];

    // Email body content.
    $form['body'] = [
      '#type' => 'text_format',
      '#format' => $content_moderation_notification->getMessageFormat() ?: filter_default_format(),
      '#title' => $this->t('Email Body'),
      '#default_value' => $content_moderation_notification->getMessage(),
      '#description' => $this->t('You may use Twig templating code in this field.'),
    ];

    // Add token tree link if module exists.
    if ($this->moduleHandler->moduleExists('token')) {
      $form['body']['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => array_unique(['user', $selected_workflow, 'node']),
        '#weight' => 10,
      ];
    }

    // Return the form.
    return $form;
  }

  /**
   * Checks for an existing content_moderation_notification.
   *
   * @param string|int $entity_id
   *   The entity ID.
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   TRUE if this format already exists, FALSE otherwise.
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state) {
    // Use the query factory to build a new entity query.
    $query = $this->entityTypeManager->getStorage('content_moderation_notification')->getQuery();

    // Query the entity ID to see if its in use.
    $result = $query->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();

    // We don't need to return the ID, only if it exists or not.
    return (bool) $result;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   *
   * To set the submit button text, we need to override actions().
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Get the basic actins from the base class.
    $actions = parent::actions($form, $form_state);

    // Change the submit button text.
    $actions['submit']['#value'] = $this->t('Save');

    // Return the result.
    return $actions;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   *
   * Saves the entity. This is called after submit() has built the entity from
   * the form values. Do not override submit() as save() is the preferred
   * method for entity form controllers.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   */
  public function save(array $form, FormStateInterface $form_state) {
    // EntityForm provides us with the entity we're working on.
    $content_moderation_notification = $this->getEntity();

    // Drupal already populated the form values in the entity object. Each
    // form field was saved as a public variable in the entity class. PHP
    // allows Drupal to do this even if the method is not defined ahead of
    // time.
    $status = $content_moderation_notification->save();

    if ($status == SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('Notification <a href=":url">%label</a> has been updated.',
          [
            '%label' => $content_moderation_notification->label(),
            ':url' => $content_moderation_notification->toUrl('edit-form')->toString(),
          ]
      ));
      $this->logger('content_moderation_notifications')->notice('Notification has been updated.', ['%label' => $content_moderation_notification->label()]);
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('Notification <a href=":url">%label</a> has been added.',
        [
          '%label' => $content_moderation_notification->label(),
          ':url' => $content_moderation_notification->toUrl('edit-form')->toString(),
        ]
      ));
      $this->logger('content_moderation_notifications')->notice('Notification has been added.', ['%label' => $content_moderation_notification->label()]);
    }

    // Redirect the user back to the listing route after the save operation.
    $form_state->setRedirect('entity.content_moderation_notification.collection');
  }

}
