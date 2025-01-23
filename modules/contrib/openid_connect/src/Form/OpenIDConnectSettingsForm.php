<?php

namespace Drupal\openid_connect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\openid_connect\OpenIDConnectClaims;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the OpenID Connect settings form.
 *
 * @package Drupal\openid_connect\Form
 */
class OpenIDConnectSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The OpenID Connect service.
   *
   * @var \Drupal\openid_connect\OpenIDConnect
   */
  protected $openIDConnect;

  /**
   * The OpenID Connect claims service.
   *
   * @var \Drupal\openid_connect\OpenIDConnectClaims
   */
  protected $claims;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\openid_connect\OpenIDConnect $openid_connect
   *   The OpenID Connect service.
   * @param \Drupal\openid_connect\OpenIDConnectClaims $claims
   *   The claims.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, OpenIDConnect $openid_connect, OpenIDConnectClaims $claims) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->openIDConnect = $openid_connect;
    $this->claims = $claims;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('openid_connect.openid_connect'),
      $container->get('openid_connect.claims')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['openid_connect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'openid_connect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = $this->configFactory()
      ->getEditable('openid_connect.settings');

    $form['always_save_userinfo'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Save user claims on every login'),
      '#description' => $this->t('If disabled, user claims will only be saved when the account is first created.'),
      '#default_value' => $settings->get('always_save_userinfo'),
    ];

    $form['override_registration_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override registration settings'),
      '#description' => $this->t('If enabled, user creation will always be allowed, even if the registration setting is set to require admin approval, or only allowing admins to create users.'),
      '#default_value' => $settings->get('override_registration_settings'),
    ];

    $form['end_session_enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logout from identity provider'),
      '#description' => $this->t('If enabled and supported by the identity provider, logging out from Drupal will also logout the user from the identity provider.'),
      '#default_value' => $settings->get('end_session_enabled'),
    ];

    $form['autostart_login'] = [
      '#title' => $this->t('Autostart login process'),
      '#type' => 'checkbox',
      '#default_value' => $settings->get('autostart_login'),
      '#description' => $this->t('Auto start login process when login, register or password reset page was requested as anonymous.'),
    ];

    $form['user_login_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('OpenID buttons display in user login form'),
      '#options' => [
        'hidden' => $this->t('Hidden'),
        'above' => $this->t('Above'),
        'below' => $this->t('Below'),
        'replace' => $this->t('Replace'),
      ],
      '#description' => $this->t("Modify the user login form to show the the OpenID login buttons. If the 'Replace' option is selected, only the OpenID buttons will be displayed. In this case, pass the 'showcore' URL parameter to return to a password-based login form."),
      '#default_value' => $settings->get('user_login_display'),
    ];

    $form['redirects'] = [
      '#title' => $this->t('Redirects'),
      '#type' => 'fieldset',
    ];

    $form['redirects']['redirect_login'] = [
      '#title' => $this->t('Login'),
      '#type' => 'textfield',
      '#description' => $this->t('Path to redirect to on client login'),
      '#default_value' => $settings->get('redirect_login'),
    ];

    $form['redirects']['redirect_logout'] = [
      '#title' => $this->t('Logout'),
      '#type' => 'textfield',
      '#description' => $this->t('Path to redirect to on client logout'),
      '#default_value' => $settings->get('redirect_logout'),
    ];

    $form['userinfo_mappings'] = [
      '#title' => $this->t('User claims mapping'),
      '#type' => 'fieldset',
      '#tree' => TRUE,
    ];

    $properties = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $properties_skip = $this->openIDConnect->userPropertiesIgnore();
    $claims = $this->claims->getOptions();
    $mappings = $settings->get('userinfo_mappings');
    foreach ($properties as $property_name => $property) {
      if (isset($properties_skip[$property_name])) {
        continue;
      }

      $form['userinfo_mappings'][$property_name] = [
        '#type' => 'select',
        '#title' => $property->getLabel(),
        '#description' => $property->getDescription(),
        '#options' => (array) $claims,
        '#empty_value' => '',
        '#empty_option' => $this->t('- No mapping -'),
        '#default_value' => $mappings[$property_name] ?? '',
      ];
    }

    /** @var \Drupal\user\Entity\Role[] $roles */
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    unset($roles['anonymous']);
    unset($roles['authenticated']);
    $role_mappings = $settings->get('role_mappings');

    // phpcs:disable Drupal.Arrays.Array.ArrayIndentation
    $form['role_mappings'] = [
      '#title' => 'EXPERIMENTAL - ' . $this->t('User role mapping'),
      '#type' => 'fieldset',
      '#description' => $this->t('For each Drupal role, provide the sets of equivalent external groups, separated by spaces. A user belonging to one of the provided groups will be assigned the configured Drupal role.') .
                        $this->t("<br/><strong>Note:</strong> The module will not update user roles with no mapped external groups. If all mappings to one of the roles are removed, users will keep that role until it is removed in the Drupal user administration."),
      '#tree' => TRUE,
    ];
    // phpcs:enable

    foreach ($roles as $role_id => $role) {
      $default = '';
      if (isset($role_mappings[$role_id]) && is_array($role_mappings[$role_id])) {
        // Surround any mappings with spaces with double quotes.
        foreach ($role_mappings[$role_id] as $key => $mapping) {
          if (strpos($mapping, ' ') !== FALSE) {
            $role_mappings[$role_id][$key] = '"' . $mapping . '"';
          }
        }
        $default = implode(' ', $role_mappings[$role_id]);
      }

      $form['role_mappings'][$role_id] = [
        '#title' => $role->label(),
        '#type' => 'textfield',
        '#default_value' => $default,
      ];
    }

    $form['advanced'] = [
      '#title' => $this->t('Advanced'),
      '#type' => 'details',
      '#open' => $settings->get('connect_existing_users'),
    ];
    $form['advanced']['connect_existing_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically connect existing users'),
      '#description' => $this->t('<strong><em>Please note:</em> This option has security implications, only use with trusted OpenID Connect providers.</strong><br />If disabled, authentication will fail for accounts with existing email addresses, users may connect existing accounts on their personal Connected Accounts page in a secure way.'),
      '#default_value' => $settings->get('connect_existing_users'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $role_mappings = [];
    foreach ($form_state->getValue('role_mappings') as $role => $mapping) {
      $values = array_values(array_filter(str_getcsv($mapping, ' ')));
      if (!empty($values)) {
        $role_mappings[$role] = $values;
      }
    }

    $this->config('openid_connect.settings')
      ->set('always_save_userinfo', $form_state->getValue('always_save_userinfo'))
      ->set('connect_existing_users', $form_state->getValue('connect_existing_users'))
      ->set('override_registration_settings', $form_state->getValue('override_registration_settings'))
      ->set('end_session_enabled', $form_state->getValue('end_session_enabled'))
      ->set('autostart_login', $form_state->getValue('autostart_login'))
      ->set('user_login_display', $form_state->getValue('user_login_display'))
      ->set('redirect_login', $form_state->getValue('redirect_login'))
      ->set('redirect_logout', $form_state->getValue('redirect_logout'))
      ->set('userinfo_mappings', array_filter($form_state->getValue('userinfo_mappings')))
      ->set('role_mappings', $role_mappings)
      ->save();
  }

}
