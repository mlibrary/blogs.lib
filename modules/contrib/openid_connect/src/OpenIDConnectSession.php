<?php

namespace Drupal\openid_connect;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Session service of the OpenID Connect module.
 */
class OpenIDConnectSession implements OpenIDConnectSessionInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The session object.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Construct an instance of the OpenID Connect session service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   The session object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RedirectDestinationInterface $redirect_destination, SessionInterface $session, LanguageManagerInterface $language_manager) {
    $this->configFactory = $config_factory;
    $this->redirectDestination = $redirect_destination;
    $this->session = $session;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): OpenIDConnectSession {
    return new static(
      $container->get('config.factory'),
      $container->get('redirect.destination'),
      $container->get('session'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDestination(bool $clear = TRUE) : array {
    $ret = [
      'destination' => $this->session->get('openid_connect_destination'),
      'langcode' => $this->session->get('openid_connect_langcode'),
    ];
    if ($clear) {
      $this->session->remove('openid_connect_destination');
      $this->session->remove('openid_connect_langcode');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveDestination() {
    // If the current request includes a 'destination' query parameter we'll use
    // that in the redirection. Otherwise use the current request path and
    // query.
    $destination = ltrim($this->redirectDestination->get(), '/');
    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    // Don't redirect to user/login. In this case redirect to the user profile.
    if (strpos($destination, ltrim(Url::fromRoute('user.login')->toString(), '/')) === 0) {
      $redirect_login = $this->configFactory->get('openid_connect.settings')->get('redirect_login');
      $destination = $redirect_login ?: 'user';
    }

    $this->session->set('openid_connect_destination', $destination);
    $this->session->set('openid_connect_langcode', $langcode);
  }

  /**
   * {@inheritDoc}
   */
  public function saveTargetLinkUri(string $target_link_uri): void {
    try {
      $uri = Url::fromUserInput($target_link_uri);
    }
    catch (\InvalidArgumentException $e) {
      // Invalid url, return.
      return;
    }

    // Make sure the uri is not external.
    if (!$uri->isExternal()) {
      // Save the path if it is safe.
      $this->session->set('openid_connect_destination', ltrim($target_link_uri, '/'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveOp(bool $clear = TRUE): array {
    $ret = [
      'op' => $this->session->get('openid_connect_op'),
      'uid' => $this->session->get('openid_connect_uid'),
    ];
    if ($clear) {
      $this->session->remove('openid_connect_op');
      $this->session->remove('openid_connect_uid');
    }

    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveOp(string $op, int $uid = NULL) {
    $this->session->set('openid_connect_op', $op);
    if (isset($uid)) {
      $this->session->set('openid_connect_uid', $uid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveIdToken(bool $clear = FALSE) : ?string {
    $ret = $this->session->get('openid_connect_id');
    if ($clear) {
      $this->session->remove('openid_connect_id');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveIdToken(string $token) {
    $this->session->set('openid_connect_id', $token);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveAccessToken(bool $clear = FALSE) : ?string {
    $ret = $this->session->get('openid_connect_access');
    if ($clear) {
      $this->session->remove('openid_connect_access');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveAccessToken(string $token) {
    $this->session->set('openid_connect_access', $token);
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveStateToken(bool $clear = TRUE) : ?string {
    $ret = $this->session->get('openid_connect_state');
    if ($clear) {
      $this->session->remove('openid_connect_state');
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function saveStateToken(string $token) {
    $this->session->set('openid_connect_state', $token);
  }

}
