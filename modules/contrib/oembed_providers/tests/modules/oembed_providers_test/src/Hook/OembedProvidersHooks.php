<?php

namespace Drupal\oembed_providers_test\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks for oEmbed Providers.
 */
class OembedProvidersHooks {

  /**
   * Alter oEmbed providers.
   */
  #[Hook('oembed_providers_alter')]
  public function providersAlter(array &$providers) {
    // Add a custom provider.
    $providers[] = [
      'provider_name' => 'My Custom Provider',
      'provider_url' => 'http://my-custom-provider.example.com',
      'endpoints' => [
        [
          'schemes' => [
            'http://my-custom-provider.example.com/id/*',
            'https://my-custom-provider.example.com/id/*',
          ],
          'url' => 'https://my-custom-provider.example.com/api/v2/oembed/',
          'discovery' => 'true',
        ],
      ],
    ];
  }

}
