<?php

namespace Drupal\autoban\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\autoban\AutobanUtils;
use Drupal\autoban\Entity\Autoban;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an Autoban functional.
 */
class AutobanController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a GroupAjaxResponseSubscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(RequestStack $requestStack) {
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

  /**
   * Operators per user type for a query condition based on the user field.
   */
  const OPERATOR = [TRUE => '>', FALSE => '='];

  /**
   * Retrieve IP addresses for autoban rule.
   *
   * @param string $rule
   *   Autoban rule ID.
   * @param array $params
   *   Params (type, message) for special query.
   *
   * @return array
   *   IP addresses as query result.
   */
  public function getBannedIp($rule, array $params = []) {
    $this->canIpBan('111.111.111.111');

    $query_mode = $this->config('autoban.settings')->get('autoban_query_mode');
    $debug_mode = $this->config('autoban.settings')->get('autoban_debug') ?? FALSE;
    $use_wildcards = $this->config('autoban.settings')->get('autoban_use_wildcards') ?: FALSE;
    $regexp_query_mode = $query_mode == 'regexp';
    $from_analyze = AutobanUtils::isFromAnalyze($rule) && !empty($params);

    if ($from_analyze) {
      $entity = NULL;
      $message = Html::decodeEntities(trim($params['message']));
      $type = trim($params['type']);
      $threshold = 1;
      $window = NULL;
      $referer = NULL;
      $user_type = AutobanUtils::AUTOBAN_USER_ANY;
    }
    else {
      $entity = Autoban::load($rule);
      $message = trim($entity->message);
      $type = trim($entity->type);
      $threshold = (int) $entity->threshold;
      $window = !empty($entity->window) ? trim($entity->window) : NULL;
      $referer = !empty($entity->referer) ? trim($entity->referer) : NULL;
      $user_type = (int) $entity->user_type;
    }

    $connection = Database::getConnection();
    $query = $connection->select('watchdog', 'log');
    $query->fields('log', ['hostname']);

    $group = $query->orConditionGroup();

    // Checking for multiple messages divided by separator.
    $message_items = explode('|', $message);
    if (count($message_items) > 1) {
      foreach ($message_items as $message_item) {
        if ($from_analyze) {
          $group->condition('log.message', trim($message_item))
            ->condition('log.variables', trim($message_item));
        }
        else {
          if ($regexp_query_mode) {
            $group->condition('log.message', trim($message_item), 'REGEXP')
              ->condition('log.variables', trim($message_item), 'REGEXP');
          }
          else {
            if (!$use_wildcards) {
              $message_item = str_replace('%', '', $message_item);
              $group->condition('log.message', '%' . $query->escapeLike(trim($message_item)) . '%', 'LIKE')
                ->condition('log.variables', '%' . $query->escapeLike(trim($message_item)) . '%', 'LIKE');
            }
            else {
              $message_item = trim($message_item);
              if (substr($message_item, 0, 1) == '%') {
                $message_item = '%' . ltrim($message_item, '%');
              }
              if (substr($message_item, -1) == '%') {
                $message_item = rtrim($message_item, '%') . '%';
              }
              $group->condition('log.message', $message_item, 'LIKE')
                ->condition('log.variables', $message_item, 'LIKE');
            }
          }
        }
      }
    }
    else {
      if ($from_analyze) {
        $group->condition('log.message', $message)
          ->condition('log.variables', $message);
      }
      else {
        if ($regexp_query_mode) {
          $group->condition('log.message', $message, 'REGEXP')
            ->condition('log.variables', $message, 'REGEXP');
        }
        else {
          if (!$use_wildcards) {
            $message = str_replace('%', '', $message);
            $group->condition('log.message', '%' . $query->escapeLike($message) . '%', 'LIKE')
              ->condition('log.variables', '%' . $query->escapeLike($message) . '%', 'LIKE');
          }
          else {
            if (substr($message, 0, 1) == '%') {
              $message = '%' . ltrim($message, '%');
            }
            if (substr($message, -1) == '%') {
              $message = rtrim($message, '%') . '%';
            }
            $group->condition('log.message', $message, 'LIKE')
              ->condition('log.variables', $message, 'LIKE');
          }
        }
      }
    }
    $query->condition('log.type', $type)
      ->condition($group);

    if (!empty($referer)) {
      $query->condition('log.referer', '%' . $query->escapeLike($referer) . '%', 'LIKE');
    }

    if ($user_type !== AutobanUtils::AUTOBAN_RULE_ANY) {
      $authenticated = in_array($user_type, [
        AutobanUtils::AUTOBAN_USER_AUTHENTICATED,
        AutobanUtils::AUTOBAN_USER_AUTHENTICATED_STRICT,
      ]);

      $query->condition('log.uid', 0, self::OPERATOR[$authenticated]);
    }

    if ($window) {
      $date = strtotime($window);
      if ($date) {
        $query->condition('log.timestamp', $date, '>=');
      }
    }

    $query->groupBy('log.hostname');
    $query->addExpression('COUNT(log.hostname)', 'hcount');
    $query->having('COUNT(log.hostname) >= :cnt', [':cnt' => $threshold]);

    if ($debug_mode) {
      $this->messenger()->addStatus((string) $query);

      $status = $this->t('Regexp query mode: %regexp_query_mode Use wildcards: %use_wildcards', [
        '%regexp_query_mode' => $regexp_query_mode ? $this->t('Yes') : $this->t('No'),
        '%use_wildcards' => $use_wildcards ? $this->t('Yes') : $this->t('No'),
      ]);
      $this->messenger()->addStatus($status);
    }

    $result = $query->execute()->fetchAll();

    if ($result && in_array($user_type, [
      AutobanUtils::AUTOBAN_USER_ANONYMOUS_STRICT,
      AutobanUtils::AUTOBAN_USER_AUTHENTICATED_STRICT,
    ])) {
      $hostnames = array_map(function ($item) {
        return $item->hostname;
      }, $result);

      $hostnames = $connection->select('watchdog', 'log')
        ->fields('log', ['hostname'])
        ->condition('hostname', $hostnames, 'IN')
        ->condition('uid', 0, self::OPERATOR[$user_type === AutobanUtils::AUTOBAN_USER_ANONYMOUS_STRICT])
        ->execute()
        ->fetchCol();

      if ($hostnames) {
        $result = array_filter($result, function ($item) use ($hostnames) {
          return !in_array($item->hostname, $hostnames);
        });
      }
    }

    return $result;
  }

  /**
   * Get IP Ban Manager data from provider name.
   *
   * @param string $provider
   *   Ban provider ID.
   *
   * @return array
   *   Ban manager object, ban_name, ban_type.
   */
  public function getBanManagerData($provider) {
    // Retrieve Ban provider data for the current provider.
    $banProvider = $this->getBanProvidersList($provider);
    if ($banProvider) {
      // Get Ban Manager object from AutobanProviderInterface implementation.
      $service = $banProvider['service'];
      if ($service) {
        $connection = Database::getConnection();
        // Return Ban Provider's Ban IP Manager and Ban Type.
        return [
          'ban_manager' => $service->getBanIpManager($connection),
          'ban_name' => $service->getName(),
          'ban_type' => $service->getBanType(),
          'ban_meta' => $service->hasMetadata(),
        ];
      }
    }

    return NULL;
  }

  /**
   * Get IP Ban Manager data from autoban rule.
   *
   * @param string $rule
   *   Autoban rule ID.
   *
   * @return array
   *   Ban manager object and ban_type.
   */
  public function getBanManagerDataRule($rule) {
    $entity = Autoban::load($rule);
    return [
      'provider' => $this->getBanManagerData($entity->provider),
      'entity' => $entity,
    ];
  }

  /**
   * Get Ban providers list.
   *
   * @param string $provider_id
   *   Ban provider ID.
   *
   * @return array
   *   List ban providers or provider's data.
   */
  public function getBanProvidersList($provider_id = NULL) {
    $banProvidersList = [];
    // phpcs:disable
    $container = \Drupal::getContainer();
    // phpcs:enable
    $kernel = $container->get('kernel');

    // Get all services list.
    $services = $kernel->getCachedContainerDefinition()['services'];
    foreach ($services as $service_id => $value) {
      // phpcs:disable
      $service_def = unserialize($value);
      // phpcs:enable
      if (!empty($service_def['properties']) && !empty($service_def['properties']['_serviceId'])) {
        $service_id = $service_def['properties']['_serviceId'];
      }

      $aservices = explode('.', $service_id);
      $service_name = end($aservices);
      if ($service_name === 'ban_provider') {
        $service = $container->get($service_id);
        $id = $service->getId();
        $name = $service->getName();
        $banProvidersList[$id] = ['name' => $name, 'service' => $service];
      }
    }

    if (!empty($provider_id)) {
      return $banProvidersList[$provider_id] ?? NULL;
    }
    else {
      return $banProvidersList;
    }
  }

  /**
   * Direct ban controller.
   *
   * @param string $ips
   *   IP addresses (comma delimited).
   * @param string $provider
   *   Ban provider name.
   */
  public function banIpAction($ips, $provider) {
    $banManagerData = $this->getBanManagerData($provider);
    $banData = [
      'provider' => $banManagerData,
      'entity' => ['id' => 'direct'],
    ];

    $ips_arr = explode(',', $ips);
    foreach ($ips_arr as $ip) {
      $banned = $this->banIp($ip, $banData, TRUE);

      if ($banned) {
        $this->messenger()->addMessage($this->t('IP %ip has been banned (@provider).', [
          '%ip' => $ip,
          '@provider' => $banManagerData['ban_name'],
        ])
        );
      }
      else {
        $this->messenger()->addMessage($this->t('IP %ip has not been banned', ['%ip' => $ip]), 'warning');
      }
    }

    $destination = $this->getDestinationArray();
    if (!empty($destination)) {
      $url = Url::fromUserInput($destination['destination']);
      return new RedirectResponse($url->toString());
    }
  }

  /**
   * Ban address.
   *
   * @param string $ip
   *   IP address.
   * @param array $banData
   *   Ban manager data.
   * @param bool $debug
   *   Show debug message.
   *
   * @return bool
   *   IP banned status.
   */
  public function banIp($ip, array $banData, $debug = FALSE) {
    if (empty($banData)) {
      if ($debug) {
        $this->messenger()->addMessage($this->t('Empty banData.'), 'warning');
      }
      return FALSE;
    }

    $banManagerData = $banData['provider'] ?? NULL;
    if (empty($banManagerData)) {
      if ($debug) {
        $this->messenger()->addMessage($this->t('Empty banManagerData.'), 'warning');
      }
      return FALSE;
    }

    $banManager = $banManagerData['ban_manager'];
    if (!$this->canIpBan($ip)) {
      if ($debug) {
        $this->messenger()->addMessage($this->t('Cannot ban this IP.'), 'warning');
      }
      return FALSE;
    }

    if ($banManager->isBanned($ip)) {
      if ($debug) {
        $this->messenger()->addMessage($this->t('This IP already banned.'), 'warning');
      }
      return FALSE;
    }

    $banType = $banManagerData['ban_type'];

    $hasMeta = $banManagerData['ban_meta'] && method_exists($banManager, 'setMetadata');
    if ($hasMeta && !empty($banData['entity'])) {
      $metadata = (array) $banData['entity'];
      $metadata['reporter'] = 'autoban';
      $banManager->setMetadata($metadata);
    }

    switch ($banType) {
      case 'single':
        $banManager->banIp($ip);
        break;

      case 'range':
        $ip_range = $this->createIpRange($ip);
        if (empty($ip_range)) {
          // If cannot create IP range banned single IP.
          $banManager->banIp($ip);
        }
        else {
          $banManager->banIp($ip_range['ip_start'], $ip_range['ip_end']);
        }
        break;
    }

    return TRUE;
  }

  /**
   * Ban addresses.
   *
   * @param array $ip_list
   *   IP addresses list.
   * @param string $rule
   *   Autoban rule ID.
   *
   * @return int
   *   IP banned count.
   */
  public function banIpList(array $ip_list, $rule) {
    $count = 0;
    if (!empty($ip_list) && $rule) {
      // Retrieve Ban data for current rule.
      $banData = $this->getBanManagerDataRule($rule);
      $banManagerData = $banData['provider'] ?? NULL;
      if ($banManagerData) {
        foreach ($ip_list as $item) {
          $banStatus = $this->banIp($item->hostname, $banData);
          if ($banStatus) {
            $count++;
            $this->messenger()->addMessage($this->t('IP %ip has been banned (@provider).', [
              '%ip' => $item->hostname,
              '@provider' => $banManagerData['ban_name'],
            ])
            );
          }
        }
      }
      else {
        $this->messenger()->addMessage($this->t('No ban manager for rule %rule', ['%rule' => $rule]), 'error');
      }
    }

    return $count;
  }

  /**
   * Check IP address for ban.
   *
   * @param string $ip
   *   IP candidate for ban.
   *
   * @return bool
   *   Can ban.
   */
  public function canIpBan($ip) {
    // You cannot ban your current IP address.
    $current_ip = $this->requestStack->getCurrentRequest()->getClientIp();
    if ($ip == $current_ip) {
      return FALSE;
    }

    // The IP address must not be whitelisted.
    if ($this->whitelistIp($ip)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Is IP address in subnet?
   *
   * @param string $ip
   *   IP address for match check.
   * @param string $network
   *   IP subnet.
   * @param int $cidr
   *   CIDR.
   *
   * @return bool
   *   IP mathes for subnet.
   */
  private function cidrMatch($ip, $network, $cidr) {
    return ((ip2long($ip) & ~((1 << (32 - $cidr)) - 1)) == ip2long($network));
  }

  /**
   * Is IP address in whitelist?
   *
   * @param string $ip
   *   IP address for check.
   *
   * @return bool
   *   IP address in whitelist.
   */
  private function whitelistIp($ip) {
    $autoban_whitelist = $this->config('autoban.settings')->get('autoban_whitelist');
    if (!empty($autoban_whitelist)) {
      $real_host = gethostbyaddr($ip);
      $autoban_whitelist_arr = explode(PHP_EOL, $autoban_whitelist);
      foreach ($autoban_whitelist_arr as $whitelist_ip) {
        $whitelist_ip = trim($whitelist_ip);
        if (empty($whitelist_ip)) {
          continue;
        }

        // Block comment.
        if (substr($whitelist_ip, 0, 1) == '#') {
          continue;
        }

        // Inline comment.
        $whitelist_ip_arr = explode('#', $whitelist_ip);
        if (count($whitelist_ip_arr) > 1) {
          $whitelist_ip = trim($whitelist_ip_arr[0]);
        }

        $whitelist_ip_arr = explode('/', $whitelist_ip);
        // CIDR match.
        if (count($whitelist_ip_arr) > 1) {
          $in_list = $this->cidrMatch($ip, $whitelist_ip_arr[0], (int) $whitelist_ip_arr[1]);
        }
        else {
          $in_list = ($whitelist_ip == $ip);
        }
        if ($in_list) {
          return TRUE;
        }

        // Check for domain.
        if ($real_host) {
          $real_host_arr = explode($whitelist_ip, $real_host);
          if (count($real_host_arr) == 2 && empty($real_host_arr[1])) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Create IP range from single IP.
   *
   * @param string $hostname
   *   IP address for ban.
   *
   * @return array
   *   IP range string for insert to ban table.
   */
  private function createIpRange($hostname) {
    // Make range IP from aaa.bbb.ccc.ddd to aaa.bbb.ccc.0 - aaa.bbb.ccc.255 .
    if (!ip2long($hostname)) {
      // Only IPV4 is available for IP range.
      return NULL;
    }
    $parts = explode('.', $hostname);
    if (count($parts) == 4) {
      $parts[3] = '0';
      $ip_start = implode('.', $parts);
      $parts[3] = '255';
      $ip_end = implode('.', $parts);
      return ['ip_start' => $ip_start, 'ip_end' => $ip_end];
    }
    return NULL;
  }

  /**
   * Get user type names list.
   *
   * @param int $index
   *   User type index (optional).
   *
   * @return string|array
   *   User type name or user type names list.
   */
  public function userTypeList($index = NULL) {
    $user_types = [
      AutobanUtils::AUTOBAN_USER_ANY => $this->t('Any'),
      AutobanUtils::AUTOBAN_USER_ANONYMOUS => $this->t('Anonymous'),
      AutobanUtils::AUTOBAN_USER_AUTHENTICATED => $this->t('Authenticated'),
      AutobanUtils::AUTOBAN_USER_ANONYMOUS_STRICT => $this->t('Anonymous strict'),
      AutobanUtils::AUTOBAN_USER_AUTHENTICATED_STRICT => $this->t('Authenticated strict'),
    ];

    if ($index === NULL) {
      return $user_types;
    }
    else {
      if (!isset($user_types[$index])) {
        $index = AutobanUtils::AUTOBAN_USER_ANY;
      }
      return $user_types[$index];
    }
  }

  /**
   * Get rule type names list.
   *
   * @param int $index
   *   Rule type index (optional).
   *
   * @return string|array
   *   User rule name or rule type names list.
   */
  public function ruleTypeList($index = NULL) {
    $rule_types = [
      AutobanUtils::AUTOBAN_RULE_ANY => $this->t('Any'),
      AutobanUtils::AUTOBAN_RULE_MANUAL => $this->t('Manual'),
      AutobanUtils::AUTOBAN_RULE_AUTO => $this->t('Automatic'),
    ];

    if ($index === NULL) {
      return $rule_types;
    }
    else {
      if (!isset($rule_types[$index])) {
        $index = AutobanUtils::AUTOBAN_RULE_ANY;
      }
      return $rule_types[$index];
    }
  }

}
