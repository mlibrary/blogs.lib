<?php

namespace Drupal\og_menu\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Drupal\og_menu\OgMenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides terms overview form for a taxonomy vocabulary.
 */
class OverviewMenuInstances extends FormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The term storage controller.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $storageController;

  /**
   * Constructs an OverviewTerms object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ogmenu_overview_instances';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OgMenuInterface $ogmenu = NULL) {
    $header = [
      ['data' => $this->t('Name')],
    ];
    $og_instance_storage = $this->entityTypeManager->getStorage('ogmenu_instance');
    $query = $og_instance_storage->getQuery()
      ->pager(50)
      ->sort('id')
      ->condition('type', $ogmenu->id());

    $rids = $query->execute();
    $entities = $og_instance_storage->loadMultiple($rids);
    $rows = [];
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    foreach ($entities as $entity) {
      $value = $entity->get(OgGroupAudienceHelperInterface::DEFAULT_FIELD)->getValue();
      if (!$value) {
        throw new \Exception('OG Menu requires an og group to be referenced.');
      }

      $rows[] = ['data' => [$entity->toLink()->toString()]];
    }

    $build['table'] = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No menu instances have been created yet.'),
    ];

    $build['pager'] = [
      '#theme' => 'pager',
      '#element' => 0,
      '#parameters' => [],
      '#route_name' => '<none>',
      '#tags' => [],
      '#quantity' => 9,
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
