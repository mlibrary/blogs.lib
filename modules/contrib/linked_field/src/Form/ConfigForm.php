<?php

namespace Drupal\linked_field\Form;

use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Config Form.
 *
 * @package Drupal\linked_field\Form
 */
class ConfigForm extends ConfigFormBase {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a ConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'linked_field.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'linked_field_config';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('linked_field.config');
    $attributes = $config->get('attributes');
    $conf = ['attributes' => $attributes];
    $config_text = Yaml::encode($conf);

    if (!$this->moduleHandler->moduleExists('yaml_editor')) {
      $message = $this->t('It is recommended to install the <a href="@yaml-editor">YAML Editor</a> module for easier editing.', [
        '@yaml-editor' => 'https://www.drupal.org/project/yaml_editor',
      ]);

      $this->messenger()->addWarning($message);
    }

    // Simplified row calculation: 3 rows per attribute + 4 extra lines.
    $rows = (count($attributes) * 3) + 4;

    $form['config'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration'),
      '#description' => $this->t('Available attributes can be defined in YAML syntax.'),
      '#default_value' => $config_text,
      '#rows' => $rows,
      '#attributes' => ['data-yaml-editor' => 'true'],
    ];

    // Use module's YAML config file for example structure.
    $module_path = $this->moduleHandler->getModule('linked_field')->getPath();
    $yml_text = file_get_contents($module_path . '/config/install/linked_field.config.yml');

    $form['example'] = [
      '#type' => 'details',
      '#title' => $this->t('Example structure'),
    ];

    $form['example']['description'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Each attribute has an optional label and description.'),
    ];

    $form['example']['code'] = [
      '#prefix' => '<pre>',
      '#suffix' => '</pre>',
      '#markup' => $yml_text,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config_text = $form_state->getValue('config') ?: 'attributes:';

    try {
      $form_state->set('config', Yaml::decode($config_text));
    }
    catch (InvalidDataTypeException $e) {
      $form_state->setErrorByName('config', $e->getMessage());
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->get('config');

    $this->config('linked_field.config')
      ->setData($config)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
