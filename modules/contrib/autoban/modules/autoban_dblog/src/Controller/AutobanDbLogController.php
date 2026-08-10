<?php

namespace Drupal\autoban_dblog\Controller;

/**
 * @file
 * Contains \Drupal\autoban_dblog\Controller\AutobanDbLogController.php .
 */

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\autoban\Controller\AutobanController;
use Drupal\dblog\Controller\DbLogController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Autoban database logging.
 */
class AutobanDbLogController extends DbLogController {

  /**
   * The autoban object.
   *
   * @var \Drupal\autoban\Controller\AutobanController
   */
  protected $autoban;

  /**
   * Construct the AutobanAnalyzeForm.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\autoban\Controller\AutobanController $autoban
   *   Autoban object.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatterInterface $date_formatter, FormBuilderInterface $form_builder, AutobanController $autoban) {
    parent::__construct($database, $module_handler, $date_formatter, $form_builder);
    $this->autoban = $autoban;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('autoban')
    );
  }

  /**
   * Override overview() method.
   */
  public function overview(?Request $request = NULL) {
    $autobanController = $this->autoban;
    $rows = [];

    $classes = static::getLogLevelClassMap();

    $this->moduleHandler->loadInclude('dblog', 'admin.inc');

    $build['dblog_filter_form'] = $this->formBuilder->getForm('Drupal\dblog\Form\DblogFilterForm');

    $header = [
      // Icon column.
      '',
      [
        'data' => $this->t('Type'),
        'field' => 'w.type',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Date'),
        'field' => 'w.wid',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      $this->t('Message'),
      [
        'data' => $this->t('User'),
        'field' => 'ufd.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('IP address'),
        'field' => 'w.hostname',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Operations'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];

    $query = $this->database->select('watchdog', 'w')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('w',
      [
        'wid',
        'uid',
        'severity',
        'type',
        'timestamp',
        'message',
        'variables',
        'link',
        'hostname',
      ]
    );
    $query->leftJoin('users_field_data', 'ufd', 'w.uid = ufd.uid');

    if (method_exists($this, 'addFilterToQuery')) {
      $this->addFilterToQuery($request, $query);
    }
    else {
      $filter = $this->buildFilterQuery($request);
      if (!empty($filter['where'])) {
        $query->where($filter['where'], $filter['args']);
      }
    }

    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $dblog) {
      $message = $this->formatMessage($dblog);

      if ($message && isset($dblog->wid)) {
        $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
        $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
        $url = Url::fromRoute('dblog.event', ['event_id' => $dblog->wid], [
          'attributes' => [
            // Provide a title for the link for useful hover hints. The
            // Attribute object will escape any unsafe HTML entities in the
            // final text.
            'title' => $title,
          ],
        ]);
        $message = Link::fromTextAndUrl($log_text, $url);
      }

      $username = [
        '#theme' => 'username',
        '#account' => $this->userStorage->load($dblog->uid),
      ];

      $ip = $dblog->hostname;
      if (!empty($ip) && $autobanController->canIpBan($ip)) {
        // Retrieve Autoban Ban Providers list.
        $providers = [];
        $banManagerList = $autobanController->getBanProvidersList();
        if (!empty($banManagerList)) {
          $destination = $this->getDestinationArray();
          foreach ($banManagerList as $id => $item) {
            $url_item = Url::fromRoute('autoban.direct_ban', [
              'ips' => $ip,
              'provider' => $id,
            ], [
              'query' => [
                'destination' => $destination['destination'],
              ],
            ]);
            $url_link = Link::fromTextAndUrl($item['name'], $url_item);
            $providers[$id] = $url_link->toString();
          }
        }
      }

      $providers_list = !empty($providers) ? ' ' . implode(', ', $providers) : '';
      $rows[] = [
        'data' => [
          // Cells.
          ['class' => ['icon']],
          $dblog->type,
          $this->dateFormatter->format($dblog->timestamp, 'short'),
          $message,
          ['data' => $username],
          $ip,
          ['data' => ['#markup' => $dblog->link . $providers_list]],
        ],
        // Attributes for table row.
        'class' => [
          Html::getClass('dblog-' . $dblog->type),
          $classes[$dblog->severity],
        ],
      ];
    }

    $build['dblog_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => 'admin-dblog', 'class' => ['admin-dblog']],
      '#empty' => $this->t('No log messages available.'),
      '#attached' => [
        'library' => ['dblog/drupal.dblog'],
      ],
    ];
    $build['dblog_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
