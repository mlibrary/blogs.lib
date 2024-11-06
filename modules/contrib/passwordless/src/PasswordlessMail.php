<?php

namespace Drupal\passwordless;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mail-related hook implementations.
 */
class PasswordlessMail implements ContainerInjectionInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The config.factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * PasswordlessMail constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   */
  public function __construct(
    Token $token,
    ConfigFactoryInterface $config_factory
  ) {
    $this->token = $token;
    $this->config = $config_factory;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('token'),
      $container->get('config.factory')
    );
  }

  /**
   * Implements hook_mail_alter().
   *
   * Overrides the callback function as used in user_mail(), in order to control
   * the generation of the one-time link in the generated email's body.
   */
  public function alter(&$message) {
    if (strpos($message['id'], 'user_password_reset') === 0) {
      $mail_config = $this->config->get('user.mail');
      $message['body'] = [];
      $variables = ['user' => $message['params']['account']];
      $token_options = [
        'langcode' => $message['langcode'],
        'callback' => $this::class . '::tokens',
        'clear' => TRUE,
      ];
      $message['body'][] = $this->token->replace($mail_config->get('password_reset.body'), $variables, $token_options);
    }
  }

  /**
   * Replaces the user:one-time-login-url token.
   *
   * @param array $replacements
   *   An associative array variable containing mappings from token names to
   *   values (for use with strtr()).
   * @param array $data
   *   An associative array of token replacement values. If the 'user' element
   *   exists, it must contain a user account object with the following
   *   properties:
   *   - login: The UNIX timestamp of the user's last login.
   *   - pass: The hashed account login password.
   * @param array $options
   *   A keyed array of settings and flags to control the token replacement
   *   process. See \Drupal\Core\Utility\Token::replace().
   *
   * @see user_mail_tokens()
   */
  public static function tokens(array &$replacements, array $data, array $options) {
    user_mail_tokens($replacements, $data, $options);
    $replacements['[user:one-time-login-url]'] = static::userPassResetUrl($data['user'], $options);
  }

  /**
   * Generates a unique passwordless login URL respecting the destination param.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account.
   * @param array $options
   *   (optional) A keyed array of settings. Supported options are:
   *   - langcode: A language code to be used when generating locale-sensitive
   *    URLs. If langcode is NULL the users preferred language is used.
   *
   * @return string
   *   The login URL.
   *
   * @see user_pass_reset_url()
   */
  protected static function userPassResetUrl(UserInterface $account, array $options = []) {
    $url = user_pass_reset_url($account, $options);
    $request = \Drupal::service('request_stack')->getCurrentRequest();
    if ($destination = $request->query->get('destination')) {
      $options['query'] = ['destination' => $destination];
    }
    return Url::fromUri($url, $options)->toString();
  }

}
