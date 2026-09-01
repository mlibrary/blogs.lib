<?php

namespace Drupal\oembed_providers\Entity;

use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the oEmbed provider entity.
 *
 * @ConfigEntityType(
 *   id = "oembed_provider",
 *   label = @Translation("oEmbed provider"),
 *   label_collection = @Translation("oEmbed Providers"),
 *   label_singular = @Translation("oembed provider"),
 *   label_plural = @Translation("oembed providers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count oembed provider",
 *     plural = "@count oembed providers",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\oembed_providers\OembedProviderAccessControlHandler",
 *     "list_builder" = "Drupal\oembed_providers\OembedProviderListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\oembed_providers\OembedProviderForm",
 *       "add" = "Drupal\oembed_providers\OembedProviderForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     }
 *   },
 *   admin_permission = "administer oembed providers",
 *   config_prefix = "provider",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "provider_url",
 *     "endpoints",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/media/oembed-providers/custom-providers/{oembed_provider}/edit",
 *     "delete-form" = "/admin/config/media/oembed-providers/custom-providers/{oembed_provider}/delete",
 *     "collection" = "/admin/config/media/oembed-providers/custom-providers",
 *   }
 * )
 */
#[ConfigEntityType(
  id: 'oembed_provider',
  label: new TranslatableMarkup('oEmbed provider'),
  label_collection: new TranslatableMarkup('oEmbed Providers'),
  label_singular: new TranslatableMarkup('oembed provider'),
  label_plural: new TranslatableMarkup('oembed providers'),
  label_count: [
    'singular' => '@count oembed provider',
    'plural' => '@count oembed providers',
  ],
  handlers: [
    'access' => \Drupal\oembed_providers\OembedProviderAccessControlHandler::class,
    'list_builder' => \Drupal\oembed_providers\OembedProviderListBuilder::class,
    'form' => [
      'edit' => \Drupal\oembed_providers\OembedProviderForm::class,
      'add' => \Drupal\oembed_providers\OembedProviderForm::class,
      'delete' => \Drupal\Core\Entity\EntityDeleteForm::class,
    ],
  ],
  admin_permission: 'administer oembed providers',
  config_prefix: 'provider',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
  ],
  config_export: [
    'id',
    'label',
    'provider_url',
    'endpoints',
  ],
  links: [
    'edit-form' => '/admin/config/media/oembed-providers/custom-providers/{oembed_provider}/edit',
    'delete-form' => '/admin/config/media/oembed-providers/custom-providers/{oembed_provider}/delete',
    'collection' => '/admin/config/media/oembed-providers/custom-providers',
  ]
)]
class OembedProvider extends ConfigEntityBase {

  /**
   * The oEmbed provider ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The oEmbed provider label.
   *
   * @var string
   */
  protected $label;

  /**
   * The oEmbed provider URL.
   *
   * @var string
   */
  protected $provider_url;

  /**
   * The oEmbed provider endpoints.
   *
   * @var array
   */
  protected $endpoints;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Dependency injection is impossible because
    // \Drupal\Core\Entity\EntityBase defines an incompatible create() method.
    \Drupal::service('keyvalue')->get('media')->delete('oembed_providers');
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, $update = TRUE) {
    parent::postDelete($storage, $update);

    // Dependency injection is impossible because
    // \Drupal\Core\Entity\EntityBase defines an incompatible create() method.
    \Drupal::service('keyvalue')->get('media')->delete('oembed_providers');
  }

}
