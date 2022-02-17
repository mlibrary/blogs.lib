<?php

namespace Drupal\og_menu\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Render\Element;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for OG Menu instance edit forms.
 *
 * @ingroup og_menu
 */
class OgMenuInstanceForm extends ContentEntityForm {

  /**
   * The menu link manager.
   *
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected $menuLinkManager;

  /**
   * The menu tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The link generator.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $linkGenerator;

  /**
   * The OG access service.
   *
   * @var \Drupal\og\OgAccessInterface $og_access
   */
  protected $ogAccess;

  /**
   * The overview tree form.
   *
   * @var array
   */
  protected $overviewTreeForm = array('#tree' => TRUE);

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\og_menu\OgMenuInstanceInterface
   */
  protected $entity;

  /**
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Constructs an OgMenuInstanceForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   The menu tree service.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The link generator.
   * @param \Drupal\og\OgAccessInterface $og_access
   *   The OG Access service.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The URL generator.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, MenuLinkManagerInterface $menu_link_manager, MenuLinkTreeInterface $menu_tree, LinkGeneratorInterface $link_generator, OgAccessInterface $og_access, UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_repository);
    $this->menuLinkManager = $menu_link_manager;
    $this->menuTree = $menu_tree;
    $this->linkGenerator = $link_generator;
    $this->ogAccess = $og_access;
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('plugin.manager.menu.link'),
      $container->get('menu.link_tree'),
      $container->get('link_generator'),
      $container->get('og.access'),
      $container->get('url_generator')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    // On entity add, no links are attached yet, so bail out here.
    if ($this->entity->isNew()) {
      return $form;
    }

    // Ensure that menu_overview_form_submit() knows the parents of this form
    // section.
    if (!$form_state->has('menu_overview_form_parents')) {
      $form_state->set('menu_overview_form_parents', []);
    }

    $form['#attached']['library'][] = 'menu_ui/drupal.menu_ui.adminforms';

    $tree = $this->menuTree->load('ogmenu-' . $this->entity->id(), new MenuTreeParameters());

    // We indicate that a menu administrator is running the menu access check.
    $this->getRequest()->attributes->set('_menu_admin', TRUE);
    $manipulators = array(
      array('callable' => 'menu.default_tree_manipulators:checkAccess'),
      array('callable' => 'menu.default_tree_manipulators:generateIndexAndSort'),
    );
    $tree = $this->menuTree->transform($tree, $manipulators);
    $this->getRequest()->attributes->set('_menu_admin', FALSE);

    // Determine the delta; the number of weights to be made available.
    $count = function(array $tree) {
      $sum = function ($carry, MenuLinkTreeElement $item) {
        return $carry + $item->count();
      };
      return array_reduce($tree, $sum);
    };
    $delta = max($count($tree), 50);

    $form['links'] = array(
      '#type' => 'table',
      '#theme' => 'table__menu_overview',
      '#header' => array(
        $this->t('Menu link'),
        array(
          'data' => $this->t('Enabled'),
          'class' => array('checkbox'),
        ),
        $this->t('Weight'),
        array(
          'data' => $this->t('Operations'),
          'colspan' => 3,
        ),
      ),
      '#attributes' => array(
        'id' => 'menu-overview',
      ),
      '#tabledrag' => array(
        array(
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'menu-parent',
          'subgroup' => 'menu-parent',
          'source' => 'menu-id',
          'hidden' => TRUE,
          'limit' => \Drupal::menuTree()->maxDepth() - 1,
        ),
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'menu-weight',
        ),
      ),
    );

    // Check if the user has the global permission to add new links to the menu
    // instance, or has this permission inside the group.
    $permission = 'add new links to og menu instance entities';
    $has_permission = $this->currentUser()->hasPermission($permission) || $this->ogAccess->userAccess($this->entity->getGroup(), $permission, $this->currentUser(), FALSE, TRUE)->isAllowed();

    // Supply the empty text.
    if ($has_permission) {
      $form['links']['#empty'] = $this->t('There are no menu links yet. <a href=":url">Add link</a>.', [
        ':url' => $this->urlGenerator->generateFromRoute('entity.ogmenu_instance.add_link', ['ogmenu_instance' => $this->entity->id()], [
          'query' => ['destination' => $this->entity->toUrl('edit-form')->toString()],
        ]),
      ]);
    }
    else {
      $form['links']['#empty'] = $this->t('There are no menu links yet.');
    }

    $links = $this->buildOverviewTreeForm($tree, $delta);
    foreach (Element::children($links) as $id) {
      if (isset($links[$id]['#item'])) {
        $element = $links[$id];

        $form['links'][$id]['#item'] = $element['#item'];

        // TableDrag: Mark the table row as draggable.
        $form['links'][$id]['#attributes'] = $element['#attributes'];
        $form['links'][$id]['#attributes']['class'][] = 'draggable';

        // TableDrag: Sort the table row according to its existing/configured weight.
        $form['links'][$id]['#weight'] = $element['#item']->link->getWeight();

        // Add special classes to be used for tabledrag.js.
        $element['parent']['#attributes']['class'] = array('menu-parent');
        $element['weight']['#attributes']['class'] = array('menu-weight');
        $element['id']['#attributes']['class'] = array('menu-id');

        $form['links'][$id]['title'] = array(
          array(
            '#theme' => 'indentation',
            '#size' => $element['#item']->depth - 1,
          ),
          $element['title'],
        );
        $form['links'][$id]['enabled'] = $element['enabled'];
        $form['links'][$id]['enabled']['#wrapper_attributes']['class'] = array('checkbox', 'menu-enabled');

        $form['links'][$id]['weight'] = $element['weight'];

        // Operations (dropbutton) column.
        $form['links'][$id]['operations'] = $element['operations'];

        $form['links'][$id]['id'] = $element['id'];
        $form['links'][$id]['parent'] = $element['parent'];
      }
    }
    return $form;
  }

  protected function buildOverviewTreeForm($tree, $delta) {
    $form = &$this->overviewTreeForm;
    $tree_access_cacheability = new CacheableMetadata();
    foreach ($tree as $element) {
      $tree_access_cacheability = $tree_access_cacheability->merge(CacheableMetadata::createFromObject($element->access));

      // Only render accessible links.
      if (!$element->access->isAllowed()) {
        continue;
      }

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $element->link;
      if ($link) {
        $id = 'menu_plugin_id:' . $link->getPluginId();
        $form[$id]['#item'] = $element;
        $form[$id]['#attributes'] = $link->isEnabled() ? array('class' => array('menu-enabled')) : array('class' => array('menu-disabled'));
        $form[$id]['title'] = Link::fromTextAndUrl($link->getTitle(), $link->getUrlObject())->toRenderable();
        if (!$link->isEnabled()) {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('disabled') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif ($id === 'menu_plugin_id:user.logout') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('<q>Log in</q> for anonymous users') . ')';
        }
        // @todo Remove this in https://www.drupal.org/node/2568785.
        elseif (($url = $link->getUrlObject()) && $url->isRouted() && $url->getRouteName() == 'user.page') {
          $form[$id]['title']['#suffix'] = ' (' . $this->t('logged in users only') . ')';
        }

        $form[$id]['enabled'] = array(
          '#type' => 'checkbox',
          '#title' => $this->t('Enable @title menu link', array('@title' => $link->getTitle())),
          '#title_display' => 'invisible',
          '#default_value' => $link->isEnabled(),
        );
        $form[$id]['weight'] = array(
          '#type' => 'weight',
          '#delta' => $delta,
          '#default_value' => $link->getWeight(),
          '#title' => $this->t('Weight for @title', array('@title' => $link->getTitle())),
          '#title_display' => 'invisible',
        );
        $form[$id]['id'] = array(
          '#type' => 'hidden',
          '#value' => $link->getPluginId(),
        );
        $form[$id]['parent'] = array(
          '#type' => 'hidden',
          '#default_value' => $link->getParent(),
        );
        // Build a list of operations.
        $operations = array();
        $operations['edit'] = array(
          'title' => $this->t('Edit'),
        );
        // Allow for a custom edit link per plugin.
        $edit_route = $link->getEditRoute();
        if ($edit_route) {
          $operations['edit']['url'] = $edit_route;
          // Bring the user back to the menu overview.
          $operations['edit']['query'] = $this->getDestinationArray();
        }
        else {
          // Fall back to the standard edit link.
          $operations['edit'] += array(
            'url' => Url::fromRoute('menu_ui.link_edit', ['menu_link_plugin' => $link->getPluginId()]),
          );
        }
        // Links can either be reset or deleted, not both.
        if ($link->isResettable()) {
          $operations['reset'] = array(
            'title' => $this->t('Reset'),
            'url' => Url::fromRoute('menu_ui.link_reset', ['menu_link_plugin' => $link->getPluginId()]),
          );
        }
        elseif ($delete_link = $link->getDeleteRoute()) {
          $operations['delete']['url'] = $delete_link;
          $operations['delete']['query'] = $this->getDestinationArray();
          $operations['delete']['title'] = $this->t('Delete');
        }
        if ($link->isTranslatable()) {
          $operations['translate'] = array(
            'title' => $this->t('Translate'),
            'url' => $link->getTranslateRoute(),
          );
        }

        // Only display the operations to which the user has access.
        foreach ($operations as $key => $operation) {
          if (!$operation['url']->access()) {
            unset($operations[$key]);
          }
        }

        $form[$id]['operations'] = [
          '#type' => 'operations',
          '#links' => $operations,
        ];
      }

      if ($element->subtree) {
        $this->buildOverviewTreeForm($element->subtree, $delta);
      }
    }

    $tree_access_cacheability
      ->merge(CacheableMetadata::createFromRenderArray($form))
      ->applyTo($form);

    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $menu = $this->entity;
    if (!$menu->isNew() || $menu->isLocked()) {
      $this->submitOverviewForm($form, $form_state);
    }

    $status = $menu->save();


    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label menu.', [
          '%label' => $menu->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label menu.', [
          '%label' => $menu->label(),
        ]));
    }
    $field_storage = FieldStorageConfig::loadByName($this->entity->getEntityTypeId(), OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    $target_type = $field_storage->getSetting('target_type');
    $group_entity = $this->entity->getGroup();
    // If possible, redirect to the group after save.
    if ($target_type && $group_entity) {
      $form_state->setRedirect('entity.' . $target_type . '.canonical', [$target_type => $group_entity->id()]);
    }
    else {
      $form_state->setRedirect('entity.ogmenu_instance.edit_form', ['ogmenu_instance' => $menu->id()]);
    }
  }

  /**
   * Submit handler for the menu overview form.
   *
   * This function takes great care in saving parent items first, then items
   * underneath them. Saving items in the incorrect order can break the tree.
   */
  protected function submitOverviewForm(array $complete_form, FormStateInterface $form_state) {
    // Form API supports constructing and validating self-contained sections
    // within forms, but does not allow to handle the form section's submission
    // equally separated yet. Therefore, we use a $form_state key to point to
    // the parents of the form section.
    $parents = $form_state->get('menu_overview_form_parents');
    $input = NestedArray::getValue($form_state->getUserInput(), $parents);
    $form = &NestedArray::getValue($complete_form, $parents);

    // When dealing with saving menu items, the order in which these items are
    // saved is critical. If a changed child item is saved before its parent,
    // the child item could be saved with an invalid path past its immediate
    // parent. To prevent this, save items in the form in the same order they
    // are sent, ensuring parents are saved first, then their children.
    // See https://www.drupal.org/node/181126#comment-632270.
    $order = is_array($input) ? array_flip(array_keys($input)) : array();
    // Update our original form with the new order.
    $form = array_intersect_key(array_merge($order, $form), $form);

    $fields = array('weight', 'parent', 'enabled');
    $form_links = $form['links'];
    foreach (Element::children($form_links) as $id) {
      if (isset($form_links[$id]['#item'])) {
        $element = $form_links[$id];
        $updated_values = array();
        // Update any fields that have changed in this menu item.
        foreach ($fields as $field) {
          if ($element[$field]['#value'] != $element[$field]['#default_value']) {
            $updated_values[$field] = $element[$field]['#value'];
          }
        }
        if ($updated_values) {
          // Use the ID from the actual plugin instance since the hidden value
          // in the form could be tampered with.
          $this->menuLinkManager->updateDefinition($element['#item']->link->getPLuginId(), $updated_values);
        }
      }
    }
  }

}
