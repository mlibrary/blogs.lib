<?php

namespace Drupal\content_moderation_notifications;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\SynchronizableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\RoleInterface;

/**
 * General service for moderation-related questions about Entity API.
 */
class Notification implements NotificationInterface {

  public function __construct(
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected MailManagerInterface $mailManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected NotificationInformationInterface $notificationInformation,
    protected RendererInterface $renderer,
    protected ConfigFactoryInterface $configFactory,
    protected ?TokenEntityMapperInterface $tokenEntityMapper = NULL,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function processEntity(EntityInterface $entity) {
    // Never process entities that syncing (for example, during a migration).
    if ($entity instanceof SynchronizableInterface && $entity->isSyncing()) {
      return;
    }

    $notifications = $this->notificationInformation->getNotifications($entity);
    if (!empty($notifications)) {
      $this->sendNotification($entity, $notifications);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function sendNotification(EntityInterface $entity, array $notifications) {
    /** @var \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $notification */
    foreach ($notifications as $notification) {
      $data['langcode'] = $this->currentUser->getPreferredLangcode();
      $data['notification'] = $notification;
      // Setup the email subject and body content.
      // Add the entity as context to aid in token and Twig replacement.
      if ($this->tokenEntityMapper) {
        $data['params']['context'] = [
          'entity' => $entity,
          'user' => $this->currentUser,
          $this->tokenEntityMapper->getTokenTypeForEntityType($entity->getEntityTypeId()) => $entity,
        ];
      }
      else {
        $data['params']['context'] = [
          'entity' => $entity,
          'user' => $this->currentUser,
          $entity->getEntityTypeId() => $entity,
        ];
      }

      // Get Subject and process any Twig templating.
      $subject = $notification->getSubject();
      $template = [
        '#type' => 'inline_template',
        '#template' => $subject,
        '#context' => $data['params']['context'],
      ];
      $subject = DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '10.3',
        currentCallable: fn() => $this->renderer->renderInIsolation($template),
        deprecatedCallable: fn() => $this->renderer->renderPlain($template),
      );
      // Remove any newlines from Subject.
      $subject = trim(str_replace("\n", ' ', $subject));
      $data['params']['subject'] = $subject;

      // Get Message, process any Twig templating, and apply input filter.
      $message = $notification->getMessage();
      $template = [
        '#type' => 'inline_template',
        '#template' => $message,
        '#context' => $data['params']['context'],
      ];
      $message = DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '10.3',
        currentCallable: fn() => $this->renderer->renderInIsolation($template),
        deprecatedCallable: fn() => $this->renderer->renderPlain($template),
      );
      $data['params']['message'] = check_markup($message, $notification->getMessageFormat());

      // Figure out who the email should be going to.
      $data['to'] = [];

      // Get Author.
      if ($notification->author and ($entity instanceof EntityOwnerInterface)) {
        if ($entity->getOwner()->isActive()) {
          $data['to'][] = $entity->getOwner()->getEmail();
        }
      }

      // Get Roles.
      foreach ($notification->getRoleIds() as $role) {
        /** @var \Drupal\Core\Entity\EntityStorageInterface $user_storage */
        $user_storage = $this->entityTypeManager->getStorage('user');
        if ($role === RoleInterface::AUTHENTICATED_ID) {
          $uids = $this->entityTypeManager
            ->getStorage('user')
            ->getQuery()
            ->condition('status', 1)
            ->accessCheck(FALSE)
            ->execute();
          /** @var \Drupal\user\UserInterface[] $role_users */
          $role_users = $user_storage->loadMultiple(array_filter($uids));
        }
        else {
          /** @var \Drupal\user\UserInterface[] $role_users */
          $role_users = $user_storage->loadByProperties(['roles' => $role]);
        }
        foreach ($role_users as $role_user) {
          if ($role_user->isActive()) {
            // Check for access to view the entity.
            if ($entity->access('view', $role_user)) {
              $data['to'][] = $role_user->getEmail();
            }
          }
        }
      }

      // Adhoc emails.
      $adhoc_emails = $notification->getEmails();
      $template = [
        '#type' => 'inline_template',
        '#template' => $adhoc_emails,
        '#context' => $data['params']['context'],
      ];
      $adhoc_emails = DeprecationHelper::backwardsCompatibleCall(
        currentVersion: \Drupal::VERSION,
        deprecatedVersion: '10.3',
        currentCallable: fn() => $this->renderer->renderInIsolation($template),
        deprecatedCallable: fn() => $this->renderer->renderPlain($template),
      );

      // Split Adhoc emails on commas and newlines.
      $adhoc_emails = array_map('trim', explode(',', preg_replace("/((\r?\n)|(\r\n?))/", ',', $adhoc_emails)));
      foreach ($adhoc_emails as $email) {
        $data['to'][] = $email;
      }

      foreach ($this->getEmailsFromUserFields($entity, $notification) as $email) {
        $data['to'][] = $email;
      }

      // Let other modules to alter the email data.
      $this->moduleHandler->alter('content_moderation_notification_mail_data', $entity, $data);

      // Remove any null values that have crept in.
      $data['to'] = array_filter($data['to']);

      // Remove any duplicates.
      $data['to'] = array_unique($data['to']);

      // Force to BCC.
      $data['params']['headers']['Bcc'] = implode(',', $data['to']);

      $recipient = '';
      if (!$notification->disableSiteMail()) {
        $recipient = $this->configFactory->get('system.site')->get('mail');
      }
      if (!empty($data['params']['headers']['Bcc'])) {
        $this->mailManager->mail('content_moderation_notifications', 'content_moderation_notification', $recipient, $data['langcode'], $data['params'], NULL, TRUE);
      }
    }
  }

  /**
   * Returns list with emails from related user fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The saved entity.
   * @param \Drupal\content_moderation_notifications\ContentModerationNotificationInterface $notification
   *   The notification to trigger.
   *
   * @return array
   *   List of emails.
   */
  protected function getEmailsFromUserFields(
    EntityInterface $entity,
    ContentModerationNotificationInterface $notification,
  ): array {
    $recipients = [];
    $fields = array_filter($notification->getUserFields(), function ($field_name) use ($entity) {
      [$entity_type, $field_name] = explode(':', $field_name, 2);
      return $entity_type === $entity->getEntityTypeId() && $entity->hasField($field_name) && !$entity->{$field_name}->isEmpty();
    });
    foreach ($fields as $field) {
      [, $field_name] = explode(':', $field, 2);
      /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
      foreach ($entity->{$field_name} as $field_item) {
        $recipients[] = $field_item->entity->getEmail();
      }
    }

    return $recipients;
  }

}
