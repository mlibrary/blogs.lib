<?php

namespace Drupal\Tests\scheduler_content_moderation_integration\Functional;

/**
 * Test if form display field weights as preserved after saving entity type.
 *
 * @group scheduler_content_moderation_integration
 */
class FormDisplayWeightTest extends SchedulerContentModerationBrowserTestBase {

  /**
   * Test form display field weights.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testFormDisplaySchedulerFieldWeight(): void {

    $this->drupalLogin($this->adminUser);

    // Test node form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('node', 'page')
      ->setComponent('publish_state', ['type' => 'scheduler_moderation', 'weight' => -55])
      ->setComponent('unpublish_state', ['type' => 'scheduler_moderation', 'weight' => -54])
      ->save();

    // Resave node type form.
    $this->drupalGet('admin/structure/types/manage/page');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Save');

    // Form display weights should not have changed.
    $form_display = $this->container->get('entity_display.repository')->getFormDisplay('node', 'page');
    $this->assertEquals(-55, $form_display->getComponent('publish_state')['weight']);
    $this->assertEquals(-54, $form_display->getComponent('unpublish_state')['weight']);

    // Test media form display.
    $this->container->get('entity_display.repository')
      ->getFormDisplay('media', 'soundtrack')
      ->setComponent('publish_state', ['type' => 'scheduler_moderation', 'weight' => -55])
      ->setComponent('unpublish_state', ['type' => 'scheduler_moderation', 'weight' => -54])
      ->save();

    // Resave media type form.
    $this->drupalGet('admin/structure/media/manage/soundtrack');
    $this->assertSession()->statusCodeEquals(200);
    $this->submitForm([], 'Save');

    // Form display weights should not have changed.
    $form_display = $this->container->get('entity_display.repository')->getFormDisplay('media', 'soundtrack');
    $this->assertEquals(-55, $form_display->getComponent('publish_state')['weight']);
    $this->assertEquals(-54, $form_display->getComponent('unpublish_state')['weight']);
  }

}
