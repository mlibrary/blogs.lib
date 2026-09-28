<?php

namespace Drupal\oembed_providers\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\media\OEmbed\ProviderRepositoryInterface;

/**
 * Hooks for Media sources.
 */
class MediaSourceHooks {

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ProviderRepositoryInterface $providerRepository,
  ) {}

  /**
   * Adds providers buckets at media sources.
   */
  #[Hook('media_source_info_alter')]
  public function mediaSourceInfoAlter(array &$sources): void {
    $buckets = $this->entityTypeManager->getStorage('oembed_provider_bucket')->loadMultiple();

    $available_providers = [];
    foreach ($this->providerRepository->getAll() as $provider) {
      $available_providers[] = $provider->getName();
    }

    foreach ($buckets as $bucket) {
      $id = 'oembed:' . $bucket->id();

      // Return providers that are 1) allowed per config and 2) exist as
      // an available provider.
      $providers = array_intersect($available_providers, $bucket->get('providers'));
      $sources[$id] = [
        'id' => $bucket->id(),
        'label' => $bucket->label(),
        'description' => $bucket->get('description'),
        'allowed_field_types' => ['string'],
        'default_thumbnail_filename' => 'no-thumbnail.png',
        'providers' => $providers,
        'class' => 'Drupal\oembed_providers\Plugin\media\Source\OEmbed',
        'default_name_metadata_attribute' => 'default_name',
        'thumbnail_uri_metadata_attribute' => 'thumbnail_uri',
        'thumbnail_width_metadata_attribute' => 'thumbnail_width',
        'thumbnail_height_metadata_attribute' => 'thumbnail_height',
        'forms' => [
          'media_library_add' => 'Drupal\media_library\Form\OEmbedForm',
        ],
        'provider' => 'oembed_providers',
      ];
    }
  }

}
