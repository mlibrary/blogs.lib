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
   * Tests the request param condition.
   */
  public function testConditions() : void {

    /* @var \Drupal\condition_query\Plugin\Condition\RequestParam $condition */
    $condition = $this->pluginManager->createInstance('request_param');
    $condition->setConfig('request_param', "test=yes\r\nfoo=no");
    $this->assertEquals('Return true on the following query parameters: test=yes, foo=no', $condition->summary());

    $request = Request::create('/my/page');
    $this->requestStack->push($request);
    $this->assertFalse($condition->execute(), 'Request without query parameters should evaluate to FALSE.');

    // Try a path including the wrong query parameter.
    $this->requestStack->pop();
    $request = Request::create('/my/page?broken=yes');
    $this->requestStack->push($request);
    $this->assertFalse($condition->execute(), 'Request with the wrong query parameter should evaluate to FALSE.');

    // Try a path including a query param set to the wrong value.
    $this->requestStack->pop();
    $request = Request::create('/my/page?test=no');
    $this->requestStack->push($request);
    $this->assertFalse($condition->execute(), 'Request with query parameter set to the wrong value should evaluate to FALSE.');

    // Try a path including a query param with the right value.
    $this->requestStack->pop();
    $request = Request::create('/my/page?test=yes');
    $this->requestStack->push($request);
    $this->assertTrue($condition->execute(), 'Request with query parameter set to the right value should evaluate to TRUE.');

    // Try a path with both query params, both wrong.
    $this->requestStack->pop();
    $request = Request::create('/my/page?test=no&foo=yes');
    $this->requestStack->push($request);
    $this->assertFalse($condition->execute(), 'Request with both query parameters set to the wrong value should evaluate to FALSE.');

    // Try a path with both query params, one right, one wrong.
    $this->requestStack->pop();
    $request = Request::create('/my/page?test=yes&foo=yes');
    $this->requestStack->push($request);
    $this->assertTrue($condition->execute(), 'Request with both query parameters and only one correct value should evaluate to TRUE.');

    // Reconfigure the condition to also reference a parameter without a value.
    $condition->setConfig('request_param', "test=yes\r\nfoo=no\r\nempty");
    $this->assertEquals('Return true on the following query parameters: test=yes, foo=no, empty', $condition->summary());

    // Try with the 'empty' parameter present.
    $this->requestStack->pop();
    $request = Request::create('/my/page?empty');
    $this->requestStack->push($request);
    $this->assertTrue($condition->execute(), 'Request with the query parameter and no value should evaluate to TRUE.');

    // Try without the 'empty' parameter.
    $this->requestStack->pop();
    $request = Request::create('/my/page');
    $this->requestStack->push($request);
    $this->assertFalse($condition->execute(), 'Request without the empty query parameter should evaluate to FALSE.');
  }

}
