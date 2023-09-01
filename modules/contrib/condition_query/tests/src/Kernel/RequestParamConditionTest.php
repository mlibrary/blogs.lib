<?php

namespace Drupal\Tests\condition_query\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests that the Request Param Condition is working properly.
 *
 * @group Plugin
 * @group condition_query
 */
class RequestParamConditionTest extends KernelTestBase {

  /**
   * The condition plugin manager under test.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $pluginManager;

  /**
   * The request stack used for testing.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user', 'condition_query'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);

    $this->pluginManager = $this->container->get('plugin.manager.condition');

    // Set the test request stack in the container.
    $this->requestStack = new RequestStack();
    $this->container->set('request_stack', $this->requestStack);
  }

  /**
   * @covers \Drupal\condition_query\Plugin\Condition\RequestParam::evaluate
   *
   * @dataProvider provideEvaluate
   *
   * @param string $request_path
   *   The request path to test with, including any query parameters.
   * @param string[] $config
   *   Array of plugin configuration to use for the test case. Keys can be any
   *   supported configuration values that the plugin accepts ('request_param').
   * @param bool $expected
   *   The expected return value from the evaluate() method.
   */
  public function testEvaluate(string $request_path, array $config, bool $expected) : void {
    /* @var \Drupal\condition_query\Plugin\Condition\RequestParam $condition */
    $condition = $this->pluginManager->createInstance('request_param');
    foreach ($config as $key => $value) {
      $condition->setConfig($key, $value);
    }
    $request = Request::create($request_path);
    $this->requestStack->push($request);
    $this->assertEquals($expected, $condition->execute());
    $this->requestStack->pop();
  }

  /**
   * Provides data for static::testEvaluate().
   *
   * @return array
   *   Array of data for each test case.
   */
  public function provideEvaluate() : array {
    return [
      'wrong query parameter' => [
        'request_path' => '/my/page?broken=yes',
        'config' => [
          'request_param' => "test=yes",
        ],
        'expected' => FALSE,
      ],
      'right parameter, wrong value' => [
        'request_path' => '/my/page?test=no',
        'config' => [
          'request_param' => "test=yes",
        ],
        'expected' => FALSE,
      ],
      'right parameter, right value' => [
        'request_path' => '/my/page?test=yes',
        'config' => [
          'request_param' => "test=yes",
        ],
        'expected' => TRUE,
      ],
      'two parameters, both wrong' => [
        'request_path' => '/my/page?test=no&foo=no',
        'config' => [
          'request_param' => "test=yes\r\nfoo=yes",
        ],
        'expected' => FALSE,
      ],
      'two parameters, one wrong, one right' => [
        'request_path' => '/my/page?test=no&foo=yes',
        'config' => [
          'request_param' => "test=yes\r\nfoo=yes",
        ],
        'expected' => TRUE,
      ],
      'parameter without a value, present in request' => [
        'request_path' => '/my/page?empty',
        'config' => [
          'request_param' => "test=yes\r\nempty",
        ],
        'expected' => TRUE,
      ],
      'parameter without a value, missing from request' => [
        'request_path' => '/my/page',
        'config' => [
          'request_param' => "test=yes\r\nempty",
        ],
        'expected' => FALSE,
      ],
    ];
  }

  /**
   * @covers \Drupal\condition_query\Plugin\Condition\RequestParam::summary
   *
   * @dataProvider provideSummary
   *
   * @param string[] $config
   *   Array of plugin configuration to use for the test case. Keys can be any
   *   supported configuration values that the plugin accepts ('request_param').
   * @param string $expected
   *   The expected summary.
   */
  public function testSummary(array $config, string $expected) : void {
    /* @var \Drupal\condition_query\Plugin\Condition\RequestParam $condition */
    $condition = $this->pluginManager->createInstance('request_param');
    foreach ($config as $key => $value) {
      $condition->setConfig($key, $value);
    }
    $this->assertEquals($expected, $condition->summary());
  }

  /**
   * Provides data for static::testSummary().
   *
   * @return array
   *   Array of data for each test case.
   */
  public function provideSummary() : array {
    return [
      'One parameter with a value' => [
        'config' => [
          'request_param' => "test=yes",
        ],
        'expected' => 'Return true on the following query parameters: test=yes',
      ],
      'One parameter with a value, negated' => [
        'config' => [
          'request_param' => "test=yes",
          'negate' => TRUE,
        ],
        'expected' => 'Do not return true on the following query parameters: test=yes',
      ],
      'Two parameters, each with a value' => [
        'config' => [
          'request_param' => "test=yes\r\nfoo=no",
        ],
        'expected' => 'Return true on the following query parameters: test=yes, foo=no',
      ],
      'Three parameters, one without a value' => [
        'config' => [
          'request_param' => "test=yes\r\nfoo=no\r\nempty",
        ],
        'expected' => 'Return true on the following query parameters: test=yes, foo=no, empty',
      ],
      'Three parameters, one without a value, negated' => [
        'config' => [
          'request_param' => "test=yes\r\nfoo=no\r\nempty",
          'negate' => TRUE,
        ],
        'expected' => 'Do not return true on the following query parameters: test=yes, foo=no, empty',
      ],

    ];
  }

}
