<?php

namespace Drupal\openid_connect;

use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;

/**
 * OpenID Connect well-known URI discovery service.
 */
class OpenIDConnectAutoDiscover {

  /**
   * The Guzzle client object.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $client;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructs an OpenIDConnectAutoDiscover object.
   *
   * @param \Drupal\Core\Http\ClientFactory $http_client_factory
   *   A Guzzle client object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(ClientFactory $http_client_factory, LoggerChannelFactoryInterface $logger_factory) {
    $this->client = $http_client_factory;
    $this->logger = $logger_factory->get('openid_connect');
  }

  /**
   * Returns request response.
   *
   * @param string $base_url
   *   The well-known configuration base URL.
   * @param string $path
   *   The relative path of the well-known configuration.
   *
   * @return array|bool
   *   A result array or FALSE.
   */
  public function fetch(string $base_url, string $path = '.well-known/openid-configuration') {
    try {
      $response = $this->client->fromOptions(['base_uri' => $base_url])->get($path);
      return Json::decode($response->getBody());
    }
    catch (RequestException $e) {
      $this->logger->warning('The auto discover URL %url seems to be broken because of error "%error".',
        ['%url' => $base_url, '%error' => $e->getMessage()]);
      return FALSE;
    }
  }

}
