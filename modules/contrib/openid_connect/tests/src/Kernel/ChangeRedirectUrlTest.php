<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests OpenIDConnectClientFormBase::changeRedirectUrl().
 *
 * Verifies the AJAX callback returns the correct render array and URL fragment
 * when the slug is set, when it is absent (falls back to entity ID), when it is
 * explicitly cleared to an empty string (also falls back to entity ID), and
 * when it can not be used as a URL path segment at all.
 *
 * @coversDefaultClass \Drupal\openid_connect\Form\OpenIDConnectClientFormBase
 * @group openid_connect
 */
class ChangeRedirectUrlTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'file',
    'openid_connect',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['openid_connect', 'system']);
  }

  /**
   * Data provider for testChangeRedirectUrl().
   *
   * Each case is:
   * - settings sub-values to place in form state (controls provider_slug).
   * - id value in form state (or NULL if absent).
   * - expected fragment the #value must contain.
   *
   * @return array<string, array{array<string,string>, string|null, string}>
   *   Test data.
   */
  public static function changeRedirectUrlProvider(): array {
    return [
      'slug set takes priority over id' => [
        ['provider_slug' => 'my-slug'],
        'entity_id',
        '/openid-connect/my-slug',
      ],
      'no slug key falls back to id' => [
        [],
        'entity_id',
        '/openid-connect/entity_id',
      ],
      'empty slug falls back to id' => [
        ['provider_slug' => ''],
        'entity_id',
        '/openid-connect/entity_id',
      ],
      'both absent shows pending placeholder' => [
        [],
        NULL,
        'Pending name input',
      ],
      // The preview is trimmed the same way validateForm() trims the stored
      // value, so it does not advertise a URL nobody will ever get.
      'surrounding whitespace is trimmed' => [
        ['provider_slug' => '  padded-slug  '],
        'entity_id',
        '/openid-connect/padded-slug',
      ],
      // The callback fires on focusout, before validation has had a chance to
      // reject the value, so it has to survive a slug that can not be turned
      // into a URL.
      'slug that is not a path segment is reported' => [
        ['provider_slug' => 'acm/idm'],
        'entity_id',
        'can not be used in a URL',
      ],
    ];
  }

  /**
   * Tests that changeRedirectUrl() returns the expected render array.
   *
   * @covers ::changeRedirectUrl
   * @dataProvider changeRedirectUrlProvider
   */
  public function testChangeRedirectUrl(
    array $slugSettings,
    ?string $id,
    string $expectedFragment,
  ): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage('openid_connect_client');

    $entity = $storage->create([
      'id' => 'test_client',
      'label' => 'Test Client',
      'plugin' => 'generic',
      'settings' => ['client_id' => 'test', 'client_secret' => 'test'],
    ]);
    $entity->save();

    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject('openid_connect_client', 'edit')
      ->setEntity($entity);

    $values = ['settings' => $slugSettings];
    if ($id !== NULL) {
      $values['id'] = $id;
    }
    $form_state = new FormState();
    $form_state->setValues($values);

    $form = [];
    $result = $form_object->changeRedirectUrl($form, $form_state);

    $this->assertSame('html_tag', $result['#type']);
    $this->assertSame('div', $result['#tag']);
    $this->assertSame('redirect-url-value', $result['#attributes']['id']);
    $this->assertStringContainsString($expectedFragment, $result['#value']);
  }

}
