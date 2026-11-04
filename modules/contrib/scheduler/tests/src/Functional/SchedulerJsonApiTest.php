<?php

namespace Drupal\Tests\scheduler\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests scheduling entities through JSON:API.
 *
 * @group scheduler
 */
#[Group('scheduler')]
#[RunTestsInSeparateProcesses]
class SchedulerJsonApiTest extends SchedulerBrowserTestBase {

  use JsonApiRequestTestTrait;

  /**
   * Additional modules required for JSON:API requests.
   *
   * @var array
   */
  protected static $modules = ['jsonapi', 'serialization', 'basic_auth'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Allow entities to be created and updated through JSON:API.
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save();
    // Build the JSON:API routes for the entity types created during set-up.
    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests that the scheduling permission is required to set the dates.
   *
   * @dataProvider dataJsonApiEntityTypes
   * @dataProvider dataNoBundleEntityTypes
   */
  #[DataProvider('dataJsonApiEntityTypes')]
  #[DataProvider('dataNoBundleEntityTypes')]
  public function testJsonApiPermission($entityTypeId, $bundle) {
    $storage = $this->entityStorageObject($entityTypeId);
    $title_field = $this->titleField($entityTypeId);
    $publish_time = strtotime('+ 6 hours', $this->requestTime);
    $unpublish_time = strtotime('+ 10 hours', $this->requestTime);
    $publish_date = $this->dateFormatter->format($publish_time, 'custom', \DateTime::RFC3339);
    $unpublish_date = $this->dateFormatter->format($unpublish_time, 'custom', \DateTime::RFC3339);

    // Check that a user with the scheduling permission can set the dates when
    // creating an entity.
    $title = 'Scheduled - ' . $this->randomMachineName(10);
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $title,
      'publish_on' => $publish_date,
      'unpublish_on' => $unpublish_date,
    ], 201);
    $entity = $this->getEntityByTitle($entityTypeId, $title);
    $this->assertEquals($publish_time, $entity->publish_on->value, 'The publish_on date is set.');
    $this->assertEquals($unpublish_time, $entity->unpublish_on->value, 'The unpublish_on date is set.');

    // Check that the user can change the dates when updating the entity.
    $this->jsonApiRequest('PATCH', $entityTypeId, $bundle, $entity->uuid(), [
      'publish_on' => $this->dateFormatter->format($publish_time + 3600, 'custom', \DateTime::RFC3339),
    ], 200);
    $storage->resetCache([$entity->id()]);
    $this->assertEquals($publish_time + 3600, $storage->load($entity->id())->publish_on->value, 'The publish_on date is changed.');

    // Remove the scheduling permission from the user. For backwards
    // compatibility the node permission name has to end with 'nodes'.
    $edit_key = ($entityTypeId == 'node') ? 'nodes' : $entityTypeId;
    foreach ($this->schedulerUser->getRoles(TRUE) as $rid) {
      user_role_revoke_permissions($rid, ["schedule publishing of $edit_key"]);
    }

    // Check that the user can no longer set the dates, but can still create and
    // update entities.
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
      'publish_on' => $publish_date,
    ], 403);
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
      'unpublish_on' => $unpublish_date,
    ], 403);
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
    ], 201);
    $this->jsonApiRequest('PATCH', $entityTypeId, $bundle, $entity->uuid(), [
      'publish_on' => $publish_date,
    ], 403);
    $this->jsonApiRequest('PATCH', $entityTypeId, $bundle, $entity->uuid(), [
      $title_field => $this->randomMachineName(10),
    ], 200);
  }

  /**
   * Tests that the dates can only be set when enabled for the entity type.
   *
   * @dataProvider dataJsonApiEntityTypes
   */
  #[DataProvider('dataJsonApiEntityTypes')]
  public function testJsonApiNonEnabledType($entityTypeId, $bundle) {
    $title_field = $this->titleField($entityTypeId);
    $publish_date = $this->dateFormatter->format(strtotime('+ 6 hours', $this->requestTime), 'custom', \DateTime::RFC3339);
    $unpublish_date = $this->dateFormatter->format(strtotime('+ 10 hours', $this->requestTime), 'custom', \DateTime::RFC3339);
    $entity_type = $this->entityTypeObject($entityTypeId, $bundle);

    // Check that the publish_on date cannot be set when publishing is not
    // enabled.
    $entity_type->setThirdPartySetting('scheduler', 'publish_enable', FALSE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', TRUE)
      ->save();
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
      'publish_on' => $publish_date,
    ], 403);
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
      'unpublish_on' => $unpublish_date,
    ], 201);

    // Check that the unpublish_on date cannot be set when unpublishing is not
    // enabled.
    $entity_type->setThirdPartySetting('scheduler', 'publish_enable', TRUE)
      ->setThirdPartySetting('scheduler', 'unpublish_enable', FALSE)
      ->save();
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
      'publish_on' => $publish_date,
    ], 201);
    $this->jsonApiRequest('POST', $entityTypeId, $bundle, NULL, [
      $title_field => $this->randomMachineName(10),
      'unpublish_on' => $unpublish_date,
    ], 403);
  }

  /**
   * Provides data for the standard entity types which JSON:API can create.
   *
   * Commerce products are excluded because creating one requires a store and a
   * product variation.
   *
   * @return array
   *   Each array item has the values: [entity type id, bundle id].
   */
  public static function dataJsonApiEntityTypes() {
    $data = self::dataStandardEntityTypes();
    unset($data['#commerce_product']);
    return $data;
  }

  /**
   * Sends a JSON:API request as the scheduler user and checks the response.
   *
   * @param string $method
   *   The HTTP method, 'POST' to create an entity or 'PATCH' to update one.
   * @param string $entityTypeId
   *   The entity type id.
   * @param string $bundle
   *   The bundle id.
   * @param string|null $uuid
   *   The UUID of the entity to update, or NULL when creating an entity.
   * @param array $attributes
   *   The entity field values, keyed by field name.
   * @param int $expected_status
   *   The expected HTTP status code.
   */
  protected function jsonApiRequest($method, $entityTypeId, $bundle, $uuid, array $attributes, $expected_status) {
    $resource_type = "$entityTypeId--$bundle";
    $data = ['type' => $resource_type, 'attributes' => $attributes];
    if ($uuid) {
      $data['id'] = $uuid;
      $url = Url::fromRoute("jsonapi.$resource_type.individual", ['entity' => $uuid]);
    }
    else {
      $url = Url::fromRoute("jsonapi.$resource_type.collection.post");
    }
    $response = $this->request($method, $url, [
      RequestOptions::HEADERS => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$this->schedulerUser->getAccountName(), $this->schedulerUser->passRaw],
      RequestOptions::BODY => Json::encode(['data' => $data]),
    ]);
    $this->assertSame($expected_status, $response->getStatusCode(), (string) $response->getBody());
  }

}
