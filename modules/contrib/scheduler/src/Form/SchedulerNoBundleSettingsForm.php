<?php

namespace Drupal\scheduler\Form;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Scheduler settings form for entity types without bundles.
 */
class SchedulerNoBundleSettingsForm extends FormBase {

  /**
   * Scheduler manager service.
   *
   * @var \Drupal\scheduler\SchedulerManager
   */
  protected $schedulerManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity display repository service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $displayRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->schedulerManager = $container->get('scheduler.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->displayRepository = $container->get('entity_display.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduler_no_bundle_settings_form';
  }

  /**
   * Returns the page title for the no-bundle settings form route.
   */
  public function title(string $entity_type_id): string {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id, FALSE);
    if (!$entity_type) {
      return (string) $this->t('Scheduler settings');
    }
    return (string) $this->t('Scheduler settings for @label', ['@label' => $entity_type->getLabel()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?string $entity_type_id = NULL) {
    if (!$entity_type_id || !$this->schedulerManager->getPlugin($entity_type_id) || $this->schedulerManager->hasBundleType($entity_type_id)) {
      throw new NotFoundHttpException();
    }
    $form_state->set('entity_type_id', $entity_type_id);

    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $config = $this->schedulerManager->entityTypeNoBundleConfig($entity_type_id);
    $site_defaults = $this->configFactory()->get('scheduler.settings');

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $content_entity = $storage->create([]);

    $singular = $entity_type->getSingularLabel();
    $plural = $entity_type->getPluralLabel();
    $params = [
      '@type' => $entity_type->getLabel(),
      '%type' => strtolower((string) $entity_type->getLabel()),
      '@singular' => $singular,
      '@plural' => $plural,
      '@item' => $singular,
      '@items' => $plural,
    ];

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Scheduler options for @plural.', $params) . '</p>',
    ];

    $form += $this->schedulerManager->buildSchedulerFormElements(
      [
        'publish_enable' => $config->get('publish_enable') ?? $site_defaults->get('default_publish_enable'),
        'publish_touch' => $config->get('publish_touch') ?? $site_defaults->get('default_publish_touch'),
        'publish_required' => $config->get('publish_required') ?? $site_defaults->get('default_publish_required'),
        'publish_revision' => $config->get('publish_revision') ?? $site_defaults->get('default_publish_revision'),
        'publish_past_date' => $config->get('publish_past_date') ?? $site_defaults->get('default_publish_past_date'),
        'publish_past_date_created' => $config->get('publish_past_date_created') ?? $site_defaults->get('default_publish_past_date_created'),
        'unpublish_enable' => $config->get('unpublish_enable') ?? $site_defaults->get('default_unpublish_enable'),
        'unpublish_required' => $config->get('unpublish_required') ?? $site_defaults->get('default_unpublish_required'),
        'unpublish_revision' => $config->get('unpublish_revision') ?? $site_defaults->get('default_unpublish_revision'),
        'fields_display_mode' => $config->get('fields_display_mode') ?? $site_defaults->get('default_fields_display_mode'),
        'expand_fieldset' => $config->get('expand_fieldset') ?? $site_defaults->get('default_expand_fieldset'),
        'show_message_after_update' => $config->get('show_message_after_update') ?? $site_defaults->get('default_show_message_after_update'),
      ],
      $entity_type->isRevisionable(),
      method_exists($content_entity, 'getCreatedTime'),
      $params,
    );

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->get('entity_type_id');
    $config = $this->configFactory()->getEditable("scheduler.no_bundle_entity_type_settings.$entity_type_id");
    $values = [
      'expand_fieldset' => $form_state->getValue('scheduler_expand_fieldset'),
      'fields_display_mode' => $form_state->getValue('scheduler_fields_display_mode'),
      'publish_enable' => $form_state->getValue('scheduler_publish_enable'),
      'publish_past_date' => $form_state->getValue('scheduler_publish_past_date'),
      'publish_past_date_created' => $form_state->getValue('scheduler_publish_past_date_created'),
      'publish_required' => $form_state->getValue('scheduler_publish_required'),
      'publish_revision' => $form_state->getValue('scheduler_publish_revision', FALSE),
      'publish_touch' => $form_state->getValue('scheduler_publish_touch'),
      'show_message_after_update' => $form_state->getValue('scheduler_show_message_after_update'),
      'unpublish_enable' => $form_state->getValue('scheduler_unpublish_enable'),
      'unpublish_required' => $form_state->getValue('scheduler_unpublish_required'),
      'unpublish_revision' => $form_state->getValue('scheduler_unpublish_revision', FALSE),
    ];
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }

    $supported_display_modes = $this->schedulerManager->getPlugin($entity_type_id)->entityFormDisplayModes();
    $all_display_modes = array_keys($this->displayRepository->getFormModes($entity_type_id));
    $all_display_modes[] = EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE;

    foreach (array_unique($all_display_modes) as $display_mode) {
      // Form modes registered only via hook_entity_form_mode_info_alter()
      // have no backing entity_form_mode config entity. Saving a display for
      // these causes a fatal in EntityDisplayBase::calculateDependencies() when
      // it calls getConfigDependencyName() on the null-loaded entity.
      if ($display_mode !== EntityDisplayRepositoryInterface::DEFAULT_DISPLAY_MODE) {
        if (!$this->entityTypeManager->getStorage('entity_form_mode')
          ->load($entity_type_id . '.' . $display_mode)) {
          continue;
        }
      }
      $form_display = $this->displayRepository->getFormDisplay($entity_type_id, $entity_type_id, $display_mode);
      $show_publish = $values['publish_enable'] && in_array($display_mode, $supported_display_modes, TRUE);
      $show_unpublish = $values['unpublish_enable'] && in_array($display_mode, $supported_display_modes, TRUE);

      if ($show_publish) {
        $form_display->setComponent('scheduler_settings', ['weight' => 50])
          ->setComponent('publish_on', ['type' => 'datetime_timestamp_no_default', 'weight' => 52]);
      }
      else {
        $form_display->removeComponent('publish_on');
      }

      if ($show_unpublish) {
        $form_display->setComponent('scheduler_settings', ['weight' => 50])
          ->setComponent('unpublish_on', ['type' => 'datetime_timestamp_no_default', 'weight' => 54]);
      }
      else {
        $form_display->removeComponent('unpublish_on');
      }

      if (!in_array($display_mode, $supported_display_modes, TRUE)) {
        $form_display->removeComponent('scheduler_settings');
      }
      $form_display->save();
    }

    $config->save();
    $this->messenger()->addStatus($this->t('Scheduler settings saved.'));
  }

}
