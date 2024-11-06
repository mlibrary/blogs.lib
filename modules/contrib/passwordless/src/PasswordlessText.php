<?php

namespace Drupal\passwordless;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Retrieves and manipulates text settings.
 */
class PasswordlessText implements PasswordlessTextInterface {

  /**
   * The passwordless.settings config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $settings;

  /**
   * PasswordlessText constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->settings = $config_factory->get('passwordless.settings');
  }

  /**
   * {@inheritDoc}
   */
  public function get($key) {
    $text = $this->settings->get('passwordless_' . $key);

    if (is_array($text)) {
      extract($text);
      $format = empty($format) ? filter_default_format() : $format;
      return check_markup($value, $format);
    }
    else {
      return Xss::filter($text);
    }
  }

}
