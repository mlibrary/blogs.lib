<?php

namespace Drupal\openid_connect\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the OpenID Connect client add and edit forms.
 */
abstract class OpenIDConnectClientFormBase extends EntityForm {

  /**
   * The plugin form manager.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an OpenIDConnectClientFormBase object.
   *
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(PluginFormFactoryInterface $plugin_form_manager, LanguageManagerInterface $language_manager) {
    $this->pluginFormFactory = $plugin_form_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : OpenIDConnectClientFormBase {
    return new static(
      $container->get('plugin_form.factory'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\openid_connect\Entity\OpenIDConnectClientEntity $entity */
    $entity = $this->entity;

    $form['#tree'] = TRUE;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    // If the entity is new, provide an AJAX-generated Redirect URL.
    if ($entity->isNew()) {
      $form['label']['#ajax'] = [
        'callback' => '::changeRedirectUrl',
        'event' => 'focusout',
        'disable-refocus' => TRUE,
        'wrapper' => 'redirect-url-value',
      ];
    }

    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
        'replace_pattern' => '([^a-z0-9_]+)|(^custom$)',
        'error' => 'The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".',
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getPluginForm($entity->getPlugin())
      ->buildConfigurationForm($form['settings'], $subform_state);

    $form['redirect_url'] = [
      '#title' => $this->t('Redirect URL'),
      '#type' => 'item',
      '#markup' => '<div id="redirect-url-value">' . $this->getRedirectUrl($entity->id()) . '</div>',
    ];

    return $form;
  }

  /**
   * Checks for an existing OpenID Connect client.
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
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function exists($entity_id, array $element, FormStateInterface $form_state): bool {
    $result = $this->entityTypeManager->getStorage('openid_connect_client')->getQuery()
      ->condition('id', $element['#field_prefix'] . $entity_id)
      ->execute();
    return (bool) $result;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get validation status from the plugins.
    try {
      /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $entity */
      $entity = $this->entity;
      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
      $this->getPluginForm($entity->getPlugin())
        ->validateConfigurationForm($form['settings'], $subform_state);
    }
    catch (InvalidPluginDefinitionException $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Set entity settings as configured.
    $values = $form_state->getValues()['settings'];
    $this->entity->set('settings', $values);

    // Call the plugin submit handler.
    try {
      /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $entity */
      $entity = $this->entity;
      $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
      $this->getPluginForm($entity->getPlugin())
        ->submitConfigurationForm($form, $subform_state);
    }
    catch (InvalidPluginDefinitionException $e) {
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state): int {
    $status = parent::save($form, $form_state);

    /** @var \Drupal\openid_connect\OpenIDConnectClientEntityInterface $entity */
    $entity = $this->entity;

    // Create an edit link.
    $edit_link = Link::fromTextAndUrl($this->t('Edit'), $entity->toUrl())->toString();

    if ($status === SAVED_UPDATED) {
      // If we edited an existing entity...
      $this->messenger()->addMessage($this->t('OpenID Connect client %label has been updated.', ['%label' => $entity->label()]));
      $this->logger('openid_connect')->notice('OpenID Connect client %label has been updated.',
        ['%label' => $entity->label(), 'alink' => $edit_link]
      );
    }
    else {
      // If we created a new entity...
      $this->messenger()->addMessage($this->t('OpenID Connect client %label has been added.', ['%label' => $entity->label()]));
      $this->logger('openid_connect')->notice('OpenID Connect client %label has been added.',
        ['%label' => $entity->label(), 'alink' => $edit_link]
      );
    }

    $form_state->setRedirect('entity.openid_connect_client.list');
    return $status;
  }

  /**
   * Retrieves the plugin form for a given OpenID connect client.
   *
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface $openid_client
   *   The OpenID Connect client plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the OpenID Connect client.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getPluginForm(OpenIDConnectClientInterface $openid_client): ?PluginFormInterface {
    if ($openid_client instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($openid_client, 'configure');
    }
    return NULL;
  }

  /**
   * Returns the redirect URL.
   *
   * @param string|null $id
   *   Route parameter ID.
   *
   * @return string
   *   The absolute URL as a string.
   */
  public function getRedirectUrl($id = ''): string {
    if ($id) {
      $route_parameters = ['openid_connect_client' => $id];
      return Url::fromRoute('openid_connect.redirect_controller_redirect', $route_parameters, [
        'absolute' => TRUE,
        'language' => $this->languageManager->getLanguage(LanguageInterface::LANGCODE_NOT_APPLICABLE),
      ])->toString();
    }
    return $this->t('Pending name input');
  }

  /**
   * AJAX callback to provide an updated Redirect URL when label is changed.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Render array with the redirect URL.
   */
  public function changeRedirectUrl(array &$form, FormStateInterface $form_state) : array {
    return ['#markup' => '<div id="redirect-url-value">' . $this->getRedirectUrl($form_state->getValue('id')) . '</div>'];
  }

}
