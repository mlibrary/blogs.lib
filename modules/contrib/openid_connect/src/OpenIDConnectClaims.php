<?php

namespace Drupal\openid_connect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\openid_connect\Plugin\OpenIDConnectClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The OpenID Connect claims service.
 *
 * @package Drupal\openid_connect
 */
class OpenIDConnectClaims implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The OpenID Connect claims.
   *
   * @var array
   */
  protected static $claims;

  /**
   * The default OpenID Connect scopes.
   *
   * @var array
   */
  protected $defaultScopes = ['openid', 'email'];

  /**
   * Constructs an OpenID Connect claims service instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler) {
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectClaims {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * Returns OpenID Connect claims.
   *
   * Allows them to be extended via an alter hook.
   *
   * @see http://openid.net/specs/openid-connect-core-1_0.html#StandardClaims
   * @see http://openid.net/specs/openid-connect-core-1_0.html#ScopeClaims
   *
   * @return array
   *   List of claims.
   */
  public function getClaims(): array {
    if (!isset(self::$claims)) {
      $claims = $this->getDefaultClaims();
      $this->moduleHandler->alter('openid_connect_claims', $claims);
      self::$claims = $claims;
    }
    return self::$claims;
  }

  /**
   * Returns OpenID Connect standard Claims as a Form API options array.
   *
   * @return array
   *   List of claims as options.
   */
  public function getOptions(): array {
    $options = [];
    foreach ($this->getClaims() as $claim_name => $claim) {
      $options[ucfirst($claim['scope'])][$claim_name] = $claim['title'];
    }
    return $options;
  }

  /**
   * Returns scopes that have to be requested based on the configured claims.
   *
   * @param \Drupal\openid_connect\Plugin\OpenIDConnectClientInterface|null $client
   *   An optional client. If one is provided, it will be asked for scopes.
   *
   * @return string
   *   Space delimited case sensitive list of ASCII scope values.
   *
   * @see http://openid.net/specs/openid-connect-core-1_0.html#ScopeClaims
   */
  public function getScopes(OpenIDConnectClientInterface $client = NULL): string {
    // If a client was provided, get the scopes from it.
    $scopes = !empty($client) ? $client->getClientScopes() : $this->defaultScopes;

    $claims_info = $this->getClaims();
    $claims = $this->configFactory->getEditable('openid_connect.settings')->get('userinfo_mappings');
    foreach ($claims as $claim) {
      if (isset($claims_info[$claim]) && !in_array($claims_info[$claim]['scope'], $scopes)) {
        $scopes[] = $claims_info[$claim]['scope'];
      }
    }
    return implode(' ', $scopes);
  }

  /**
   * Return default claims supported by the OpenID Connect module.
   *
   * @return array
   *   Default claims supported by the OpenID Connect module.
   */
  protected function getDefaultClaims(): array {
    return [
      'name' => [
        'scope' => 'profile',
        'title' => $this->t('Name'),
        'type' => 'string',
        'description' => $this->t('Full name'),
      ],
      'given_name' => [
        'scope' => 'profile',
        'title' => $this->t('Given name'),
        'type' => 'string',
        'description' => $this->t('Given name(s) or first name(s)'),
      ],
      'family_name' => [
        'scope' => 'profile',
        'title' => $this->t('Family name'),
        'type' => 'string',
        'description' => $this->t('Surname(s) or last name(s)'),
      ],
      'middle_name' => [
        'scope' => 'profile',
        'title' => $this->t('Middle name'),
        'type' => 'string',
        'description' => $this->t('Middle name(s)'),
      ],
      'nickname' => [
        'scope' => 'profile',
        'title' => $this->t('Nickname'),
        'type' => 'string',
        'description' => $this->t('Casual name'),
      ],
      'preferred_username' => [
        'scope' => 'profile',
        'title' => $this->t('Preferred username'),
        'type' => 'string',
        'description' => $this->t('Shorthand name by which the End-User wishes to be referred to'),
      ],
      'profile' => [
        'scope' => 'profile',
        'title' => $this->t('Profile'),
        'type' => 'string',
        'description' => $this->t('Profile page URL'),
      ],
      'picture' => [
        'scope' => 'profile',
        'title' => $this->t('Picture'),
        'type' => 'string',
        'description' => $this->t('Profile picture URL'),
      ],
      'website' => [
        'scope' => 'profile',
        'title' => $this->t('Website'),
        'type' => 'string',
        'description' => $this->t('Web page or blog URL'),
      ],
      'email' => [
        'scope' => 'email',
        'title' => $this->t('Email'),
        'type' => 'string',
        'description' => $this->t('Preferred e-mail address'),
      ],
      'email_verified' => [
        'scope' => 'email',
        'title' => $this->t('Email verified'),
        'type' => 'boolean',
        'description' => $this->t('True if the e-mail address has been verified; otherwise false'),
      ],
      'gender' => [
        'scope' => 'profile',
        'title' => $this->t('Gender'),
        'type' => 'string',
        'description' => $this->t('Gender'),
      ],
      'birthdate' => [
        'scope' => 'profile',
        'title' => $this->t('Birthdate'),
        'type' => 'string',
        'description' => $this->t('Birthday'),
      ],
      'zoneinfo' => [
        'scope' => 'profile',
        'title' => $this->t('Zoneinfo'),
        'type' => 'string',
        'description' => $this->t('Time zone'),
      ],
      'locale' => [
        'scope' => 'profile',
        'title' => $this->t('Locale'),
        'type' => 'string',
        'description' => $this->t('Locale'),
      ],
      'phone_number' => [
        'scope' => 'phone',
        'title' => $this->t('Phone number'),
        'type' => 'string',
        'description' => $this->t('Preferred telephone number'),
      ],
      'phone_number_verified' => [
        'scope' => 'phone',
        'title' => $this->t('Phone number verified'),
        'type' => 'boolean',
        'description' => $this->t('True if the phone number has been verified; otherwise false'),
      ],
      'address' => [
        'scope' => 'address',
        'title' => $this->t('Address'),
        'type' => 'json',
        'description' => $this->t('Preferred postal address'),
      ],
      'updated_at' => [
        'scope' => 'profile',
        'title' => $this->t('Updated at'),
        'type' => 'number',
        'description' => $this->t('Time the information was last updated'),
      ],
    ];
  }

}
