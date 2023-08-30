<?php

namespace Drupal\Tests\views\Functional\Plugin;

use Drupal\Tests\taxonomy\Functional\Views\TaxonomyTestBase;
use Drupal\views\Views;

/**
 * Tests the handler of the view: entity target argument.
 *
 * @group views
 * @see \Drupal\views\Plugin\views\argument\EntityArgument
 */
class EntityArgumentTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_entity_id_argument'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'taxonomy', 'views_test_config'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An user with permissions to administer taxonomy.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE, $modules = []): void {
    parent::setUp($import_test_views);

    // Create an administrative user.
    $this->adminUser = $this->drupalCreateUser(['administer taxonomy', 'bypass node access']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the generated title of a view: entity target argument.
   */
  public function testArgumentTitle() {
    $view = Views::getView('test_entity_id_argument');
    $assert_session = $this->assertSession();

    // Test with single entity ID examples.
    $this->drupalGet('/entity-id-argument-test');
    $assert_session->titleEquals($view->getTitle() . ' | Drupal');
    $this->drupalGet('/entity-id-argument-test/1');
    $assert_session->titleEquals('test: title ' . $this->term1->label() . ', input ' . $this->term1->id() . ' | Drupal');
    $this->drupalGet('/entity-id-argument-test/2');
    $assert_session->titleEquals('test: title ' . $this->term2->label() . ', input ' . $this->term2->id() . ' | Drupal');

    // Test with multiple entity IDs examples.
    $this->drupalGet('/entity-id-argument-test/1,2');
    $assert_session->titleEquals('test: title ' . $this->term1->label() . ', ' . $this->term2->label() . ', input ' . $this->term1->id() . ',' . $this->term2->id() . ' | Drupal');
    $this->drupalGet('/entity-id-argument-test/2,1');
    $assert_session->titleEquals('test: title ' . $this->term2->label() . ', ' . $this->term1->label() . ', input ' . $this->term2->id() . ',' . $this->term1->id() . ' | Drupal');
    $this->drupalGet('/entity-id-argument-test/1+2');
    $assert_session->titleEquals('test: title ' . $this->term1->label() . ' + ' . $this->term2->label() . ', input ' . $this->term1->id() . '+' . $this->term2->id() . ' | Drupal');
    $this->drupalGet('/entity-id-argument-test/2+1');
    $assert_session->titleEquals('test: title ' . $this->term2->label() . ' + ' . $this->term1->label() . ', input ' . $this->term2->id() . '+' . $this->term1->id() . ' | Drupal');
  }

}
