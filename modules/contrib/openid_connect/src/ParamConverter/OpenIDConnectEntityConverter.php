<?php

declare(strict_types=1);

namespace Drupal\openid_connect\ParamConverter;

use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting provider slugs to OpenIDConnect clients.
 */
class OpenIDConnectEntityConverter extends EntityConverter {

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $storage = $this->entityTypeManager->getStorage('openid_connect_client');

    // Does the client exist with the value provided as the id?
    // Always allow the ID as a redirect URL.
    $client = $storage->load($value);

    if ($client instanceof OpenIDConnectClientEntityInterface) {
      return $client;
    }

    // No client exists, attempt to find a client by the `provider_slug` value.
    $clients = $storage->loadByProperties(['settings.provider_slug' => $value]);
    // The provider slug _must_ be unique.
    // If more than one slug is found, don't allow the conversion.
    return count($clients) === 1 ? reset($clients) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return ($definition['type'] ?? NULL) === 'openid_connect_entity';
  }

}
