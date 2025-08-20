<?php

declare(strict_types=1);

namespace Drupal\og\Plugin\views\argument_default;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsArgumentDefault;
use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Drupal\og\MembershipManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the group memberships from the current user.
 */
#[ViewsArgumentDefault(
  id: 'og_group_membership',
  title: new TranslatableMarkup('Group memberships from current user'),
)]
class Membership extends ArgumentDefaultPluginBase implements CacheableDependencyInterface {

  /**
   * The OG context provider.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected ContextProviderInterface $ogContext;

  /**
   * The OG membership manager.
   */
  protected MembershipManagerInterface $ogMembership;

  /**
   * The user to be evaluated.
   */
  protected AccountInterface $ogUser;

  /**
   * Constructs a new Membership instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\MembershipManagerInterface $og_membership
   *   The OG membership manager.
   * @param \Drupal\Core\Session\AccountInterface $og_user
   *   The user to be evaluated.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MembershipManagerInterface $og_membership, AccountInterface $og_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogMembership = $og_membership;
    $this->ogUser = $og_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.membership_manager'),
      $container->get('current_user')->getAccount()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    // Currently restricted to node entities.
    return implode(',', $this->getCurrentUserGroupIds());
  }

  /**
   * Returns groups that current user is a member of.
   *
   * @param string $entity_type
   *   The entity type, defaults to 'node'.
   *
   * @return array
   *   An array of groups, or an empty array if no group is found.
   */
  protected function getCurrentUserGroupIds($entity_type = 'node') {
    $groups = $this->ogMembership->getUserGroupIds($this->ogUser->id());
    if (!empty($groups) && isset($groups[$entity_type])) {
      return $groups[$entity_type];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['user'];
  }

}
