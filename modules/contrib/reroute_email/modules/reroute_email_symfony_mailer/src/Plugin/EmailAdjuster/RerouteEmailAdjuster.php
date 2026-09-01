<?php

namespace Drupal\reroute_email_symfony_mailer\Plugin\EmailAdjuster;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\reroute_email\Form\SettingsForm;
use Drupal\reroute_email\RerouteEmailHandlerPluginInterface;
use Drupal\reroute_email\RerouteEmailHandlerPluginManager;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailAdjusterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the Reroute Email Adjuster.
 *
 * @EmailAdjuster(
 *   id = "reroute_email",
 *   label = @Translation("Reroute Email"),
 *   description = @Translation("Reroute Email."),
 * )
 */
class RerouteEmailAdjuster extends EmailAdjusterBase implements ContainerFactoryPluginInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The reroute email handler plugin manager.
   *
   * @var \Drupal\reroute_email\RerouteEmailHandlerPluginManager
   */
  protected RerouteEmailHandlerPluginManager $rerouteHandlerManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.reroute_email_handler')
    );
  }

  /**
   * Constructs a new object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\reroute_email\RerouteEmailHandlerPluginManager $reroute_handler_manager
   *   The reroute email handler plugin manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    RerouteEmailHandlerPluginManager $reroute_handler_manager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->rerouteHandlerManager = $reroute_handler_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['use_global'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use global configuration'),
      '#description' => $this->t('Use default settings from Reroute Email <a href="@link" target="_blank">settings page</a>.',
        ['@link' => Url::fromRoute('reroute_email.settings')->toString()]
      ),
      '#default_value' => $this->configuration['use_global'] ?? TRUE,
      '#weight' => -100,
    ];

    $form['container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="config[reroute_email][use_global]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    SettingsForm::buildSettingsForm($form['container'], $this->getParams());

    /** @var \Drupal\symfony_mailer\Entity\MailerPolicyInterface $policy_entity */
    $policy_entity = $form_state->getBuildInfo()["callback_object"]->getEntity();
    if ($policy_entity->getSubType() !== NULL && $policy_entity->getType() !== NULL) {
      $form['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER]['#access'] = FALSE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build(EmailInterface $email) {
    $this->rerouteHandlerManager->processAllByType('symfony_mailer_adjuster', $email, TRUE, FALSE, $this->getParams());
  }

  /**
   * {@inheritdoc}
   */
  public function postRender(EmailInterface $email) {
    $this->rerouteHandlerManager->processAllByType('symfony_mailer_adjuster', $email, FALSE, TRUE, $this->getParams());
  }

  /**
   * Provide parameters for rerouting.
   *
   * @return array|null
   *   An array of parameters to be used in rerouting or null if defaulted.
   */
  private function getParams(): ?array {
    if ($this->configuration['use_global'] ?? TRUE) {
      return NULL;
    }

    return [
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ADDRESS] ?? $this->configFactory->get('system.site')->get('mail'),
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ALLOWLIST],
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_ROLES],
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_DESCRIPTION],
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MESSAGE],
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_SKIP],
      RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS => $this->configuration['container'][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS_WRAPPER][RerouteEmailHandlerPluginInterface::REROUTE_EMAIL_MAILKEYS],
    ];
  }

}
