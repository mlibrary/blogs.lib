<?php

declare(strict_types=1);

namespace Drupal\scheduler_no_bundle_test\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Default entity form for scheduler_test_no_bundle.
 *
 * Emits a save status message so tests can assert against
 * entitySavedMessage() without needing @internal EntityTestForm.
 */
class SchedulerTestNoBundleForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $entity = $this->getEntity();
    $is_new = $entity->isNew();
    $status = parent::save($form, $form_state);
    $message = $is_new
      ? $this->t('%entity_type @id has been created.', [
        '@id' => $entity->id(),
        '%entity_type' => $entity->getEntityTypeId(),
      ])
      : $this->t('%entity_type @id has been updated.', [
        '@id' => $entity->id(),
        '%entity_type' => $entity->getEntityTypeId(),
      ]);
    $this->messenger()->addStatus($message);
    if ($entity->id()) {
      $entity_type_id = $entity->getEntityTypeId();
      $form_state->setRedirect(
        "entity.$entity_type_id.edit_form",
        [$entity_type_id => $entity->id()]
      );
    }
    return $status;
  }

}
