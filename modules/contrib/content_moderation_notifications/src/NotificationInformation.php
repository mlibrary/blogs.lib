<?php

namespace Drupal\content_moderation_notifications;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;

/**
 * Service for notification related questions about the moderated entity.
 */
class NotificationInformation implements NotificationInformationInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * General service for moderation-related questions about Entity API.
   *
   * @var \Drupal\content_moderation\ModerationInformation
   */
  protected $moderationInformation;


  /**
   * Flag that indicates if the previous state is real or defaulted.
   *
   * Defaulted means there is no previous state. However, because class needs
   * to work with a real state, the workflow's default one is used. This flag
   * is used to detect this case.
   *
   * @var bool
   */
  protected $previousStateIsDefaulted = FALSE;

  /**
   * Creates a new NotificationInformation instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The bundle information service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public function isModeratedEntity(EntityInterface $entity) {
    return $this->moderationInformation->isModeratedEntity($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviousState(ContentEntityInterface $entity) {
    $previous_state = FALSE;
    $workflow = $this->getWorkflow($entity);
    if (isset($entity->last_revision)) {
      $previous_state = $workflow->getTypePlugin()->getState($entity->last_revision->moderation_state->value);
    }

    if (!$previous_state) {
      $previous_state = $workflow->getTypePlugin()->getInitialState($entity);
      $this->previousStateIsDefaulted = TRUE;
    }

    return $previous_state;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflow(ContentEntityInterface $entity) {
    return $this->isModeratedEntity($entity) ? $this->moderationInformation
      ->getWorkflowForEntity($entity) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTransition(ContentEntityInterface $entity) {
    $transition = FALSE;
    if (($workflow = $this->getWorkflow($entity))) {
      $current_state = $entity->moderation_state->value;
      $previous_state = $this->getPreviousState($entity)->id();

      try {
        $transition = $workflow->getTypePlugin()->getTransitionFromStateToState($previous_state, $current_state);
      }
      catch (\InvalidArgumentException $e) {
        // There is no available transition. Fall through to return FALSE.
      }
    }

    return $transition;
  }

  /**
   * Determines whether a transition represents an actual change of state.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity undergoing a workflow transition.
   *
   * @return bool
   *   Whether the transition's "from" and "to" states differ.
   */
  protected function isTransitionChange(ContentEntityInterface $entity) {
    $current_state = $entity->moderation_state->value;
    $previous_state = $this->getPreviousState($entity)->id();
    return $this->previousStateIsDefaulted || ($current_state != $previous_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getNotifications(EntityInterface $entity) {
    $notifications = [];

    if ($this->isModeratedEntity($entity)) {
      $workflow = $this->getWorkflow($entity);
      if ($transition = $this->getTransition($entity)) {
        // If there's no real change in state, don't notify.
        if (!$this->isTransitionChange($entity)) {
          return [];
        }

        // Find out if we have a config entity that contains this transition.
        $query = $this->entityTypeManager->getStorage('content_moderation_notification')
          ->getQuery()
          ->accessCheck(FALSE)
          ->condition('workflow', $workflow->id())
          ->condition('status', 1)
          ->condition('transitions.' . $transition->id(), $transition->id());

        $notification_ids = $query->execute();

        $notifications = $this->entityTypeManager
          ->getStorage('content_moderation_notification')
          ->loadMultiple($notification_ids);
      }
    }

    return $notifications;
  }

  /**
   * {@inheritdoc}
   */
  public function getLatestRevision($entity_type_id, $entity_id) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if ($storage instanceof RevisionableStorageInterface
      && $revision_id = $storage->getLatestRevisionId($entity_id)) {
      return $storage->loadRevision($revision_id);
    }
  }

}
