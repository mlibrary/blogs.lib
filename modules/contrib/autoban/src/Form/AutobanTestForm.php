<?php

namespace Drupal\autoban\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\autoban\AutobanUtils;
use Drupal\autoban\Controller\AutobanController;
use Drupal\autoban\Entity\Autoban;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Test autoban rule.
 */
class AutobanTestForm extends FormBase {

  /**
   * The autoban object.
   *
   * @var \Drupal\autoban\Controller\AutobanController
   */
  protected $autoban;

  /**
   * Construct the AutobanTestForm.
   *
   * @param \Drupal\autoban\Controller\AutobanController $autoban
   *   Autoban object.
   */
  public function __construct(AutobanController $autoban) {
    $this->autoban = $autoban;
  }

  /**
   * Factory method for AutobanTestForm.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('autoban')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'autoban_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $rule = '') {
    $build = [];

    $from_analyze = FALSE;
    if (AutobanUtils::isFromAnalyze($rule)) {
      $params = $this->getRequest()->query->all();
      $from_analyze = !empty($params['type']) && !empty($params['message']);
    }
    else {
      $params = [];
    }

    if (!empty($rule)) {
      $entity = Autoban::load($rule);
      if ($entity) {
        $build['info'] = [
          '#markup' => $this->t('<label>ID:</label> %id <label>Type:</label> %type <label>Message:</label> %message', [
            '%id' => $entity->id(),
            '%type' => $entity->type,
            '%message' => $entity->message,
          ]),
          '#allowed_tags' => ['label'],
        ];
      }
    }

    $header = [
      $this->t('Count'),
      $this->t('Ip address'),
    ];
    $controller = $this->autoban;
    if ($from_analyze) {
      $result = $controller->getBannedIp($rule, $params);
      $ips_arr = [];
    }
    else {
      $result = $controller->getBannedIp($rule);
      $header[] = $this->t('Ban status');
    }

    $rows = [];
    $ips_arr = [];

    if (!empty($result)) {
      $banManager = NULL;
      if (!$from_analyze) {
        $banData = $controller->getBanManagerDataRule($rule);
        $banManagerData = $banData['provider'] ?? NULL;
        if ($banManagerData) {
          $banManager = $banManagerData['ban_manager'];
        }
      }

      // Rows collect.
      foreach ($result as $item) {
        $data = [
          $item->hcount,
          $item->hostname,
        ];
        if ($from_analyze) {
          $ips_arr[] = $item->hostname;
        }
        else {
          $data[] = $banManager ? ($banManager->isBanned($item->hostname) ? $this->t('Banned') : $this->t('Not banned')) : '?';
        }

        $rows[] = ['data' => $data];
      }

      // Add action buttons.
      $buttons = [];
      $destination = ['destination' => Url::fromRoute('<current>', $params)->toString()];
      if (!$from_analyze && !empty($banManagerData)) {
        $entity = Autoban::load($rule);
        $url = Url::fromRoute('autoban.ban', ['rule' => $entity->id()],
          [
            'query' => $destination,
            'attributes' => [
              'class' => 'button button-action button--primary button--small',
            ],
          ]
        );
        $text = $this->t('Ban IP (Name: @name Type: @type)', [
          '@name' => $banManagerData['ban_name'],
          '@type' => $banManagerData['ban_type'],
        ]);
        $buttons['ban'] = Link::fromTextAndUrl($text, $url)->toString();
      }
      else {
        if (count($ips_arr)) {
          $banManagerList = $controller->getBanProvidersList();
          if (!empty($banManagerList)) {
            $ips = implode(',', $ips_arr);
            foreach ($banManagerList as $id => $item) {
              $url = Url::fromRoute('autoban.direct_ban', [
                'ips' => $ips,
                'provider' => $id,
              ], [
                'query' => $destination,
                'attributes' => ['class' => 'button button-action button--primary button--small'],
              ]);
              $buttons[$id] = Link::fromTextAndUrl($item['name'], $url)->toString();
            }
          }
        }
      }
      $build['buttons'] = [
        '#theme' => 'item_list',
        '#items' => $buttons,
        '#attributes' => ['class' => 'action-links'],
      ];
    }

    $build['test_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No hostnames was found.'),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
