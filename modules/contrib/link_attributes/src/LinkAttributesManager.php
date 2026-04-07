<?php

namespace Drupal\link_attributes;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides the link_attributes plugin manager.
 */
class LinkAttributesManager extends DefaultPluginManager implements PluginManagerInterface {

  /**
   * Provides default values for all link_attributes plugins.
   *
   * @var array
   */
  protected $defaults = [
    'title' => '',
    'type' => '',
    'description' => '',
  ];

  /**
   * Constructs a LinkAttributesManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->alterInfo('link_attributes_plugin');
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'link_attributes', ['link_attributes']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('link_attributes', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('title');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);

    // Make sure each plugin definition had at least a field type.
    if (empty($definition['type'])) {
      $definition['type'] = 'textfield';
    }
    // Translate options.
    if (!empty($definition['options'])) {
      $definition['options'] = $this->translateOptions($definition['options']);
    }
  }

  /**
   * Translate options, preserving optgroups.
   *
   * @param array<string,mixed> $options
   *   Array of options, possibly grouped.
   *
   * @return array<string,mixed>
   *   Array with optgroups and option values translated.
   */
  private function translateOptions(array $options): array {
    $translated = [];
    foreach ($options as $property => $option) {
      if (is_array($option)) {
        $translated[(string) new TranslatableMarkup($property)] = $this->translateOptions($option); // phpcs:ignore
        continue;
      }
      $translated[$property] = new TranslatableMarkup($option); // phpcs:ignore
    }
    return $translated;
  }

}
