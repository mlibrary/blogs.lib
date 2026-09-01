<?php

declare(strict_types=1);

namespace Drupal\Tests\openid_connect\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests moving 'openid_connect_authmap' records into the 'authmap' table.
 *
 * @see https://www.drupal.org/project/openid_connect/issues/3393143
 *
 * @group openid_connect
 */
class OpenIdConnectAuthmapUpdate8204Test extends KernelTestBase {

  /**
   * The batch size used by openid_connect_update_8204().
   */
  const BATCH_SIZE = 500;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'dblog',
    'externalauth',
    'file',
    'openid_connect',
    'system',
    'user',
  ];

  /**
   * The database connection.
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->database = $this->container->get('database');
    $this->installSchema('dblog', ['watchdog']);
    $this->installSchema('externalauth', ['authmap']);
    $this->createLegacyAuthmapTable();

    \Drupal::moduleHandler()->loadInclude('openid_connect', 'install');

    // openid_connect_requirements() uses the REQUIREMENT_* constants.
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
  }

  /**
   * Tests that a full run moves every record and drops the legacy table.
   *
   * @covers ::openid_connect_update_8204
   * @covers ::openid_connect_update_8205
   */
  public function testAllRecordsAreMoved(): void {
    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'sub-1'],
      ['uid' => 2, 'client_name' => 'generic', 'sub' => 'sub-2'],
      ['uid' => 2, 'client_name' => 'okta', 'sub' => 'sub-2'],
    ]);

    $result = $this->runUpdate8204();

    $this->assertSame([
      '1:openid_connect.generic' => 'sub-1',
      '2:openid_connect.generic' => 'sub-2',
      '2:openid_connect.okta' => 'sub-2',
    ], $this->getAuthmapRecords());

    $this->assertSame(0, $this->countLegacyRecords());
    $this->assertSame([], $this->getLoggedErrors());

    $this->assertSame(1, $result['passes']);
    $this->assertSame(1, $result['progress'][0]);
    $this->assertStringContainsString('Moved 3 record(s)', $result['message']);
    $this->assertStringNotContainsString('remain in the openid_connect_authmap table', $result['message']);

    // Running the update again is a no-op rather than a second migration.
    $rerun = $this->runUpdate8204();
    $this->assertCount(3, $this->getAuthmapRecords());
    $this->assertStringContainsString('Moved 0 record(s)', $rerun['message']);

    // With nothing left behind, the legacy table is dropped as before.
    $this->assertSame('Dropped the openid_connect_authmap table.', openid_connect_update_8205());
    $this->assertFalse($this->database->schema()->tableExists('openid_connect_authmap'));
  }

  /**
   * Tests that a '-' in the client name is sanitized into the provider.
   *
   * Update 8200 replaces '-' with '_' when it builds the
   * configuration entity id, and the runtime provider name derives from that
   * id. The migration has to sanitize identically or the mapping never matches
   * again and the account is silently unlinked.
   *
   * @see https://www.drupal.org/project/openid_connect/issues/3359789
   *
   * @covers ::openid_connect_update_8204
   */
  public function testDashedClientNameIsSanitized(): void {
    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'windows-aad', 'sub' => 'sub-1'],
    ]);

    $this->runUpdate8204();

    $this->assertSame(
      ['1:openid_connect.windows_aad' => 'sub-1'],
      $this->getAuthmapRecords()
    );
    $this->assertSame(0, $this->countLegacyRecords());
    $this->assertSame([], $this->getLoggedErrors());
  }

  /**
   * Tests that the newest of several duplicates for one user wins.
   *
   * Records are keyed on (uid, provider) in 'authmap', so a user with more than
   * one entry for the same client can only keep a single 'sub'. The record with
   * the highest 'aid' is the most recent one, and must be the one that
   * survives.
   *
   * @covers ::openid_connect_update_8204
   */
  public function testNewestDuplicateForSameUserWins(): void {
    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'oldest'],
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'middle'],
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'newest'],
    ]);

    $result = $this->runUpdate8204();

    $this->assertSame(['1:openid_connect.generic' => 'newest'], $this->getAuthmapRecords());
    $this->assertSame(0, $this->countLegacyRecords());
    $this->assertSame([], $this->getLoggedErrors());

    // Every duplicate is accounted for rather than silently discarded, so the
    // update reports completion without needing an extra empty pass.
    $this->assertSame(1, $result['passes']);
    $this->assertStringContainsString('Moved 3 record(s)', $result['message']);
  }

  /**
   * Tests that duplicates straddling a batch boundary keep the newest 'sub'.
   *
   * Duplicates for one user are only resolved correctly if each pass deletes
   * just the records it moved. Deleting by uid and client_name instead discards
   * the duplicates that the next pass would have merged, so the oldest 'sub'
   * ends up winning.
   *
   * @covers ::openid_connect_update_8204
   */
  public function testDuplicatesSpanningBatchesKeepNewest(): void {
    // Fill the first batch, so that the last record in it is a duplicate whose
    // newer counterpart is the first record of the second batch.
    $records = [];
    for ($i = 1; $i < self::BATCH_SIZE; $i++) {
      $records[] = ['uid' => $i, 'client_name' => 'generic', 'sub' => 'sub-' . $i];
    }
    $records[] = ['uid' => 9999, 'client_name' => 'generic', 'sub' => 'older'];
    $records[] = ['uid' => 9999, 'client_name' => 'generic', 'sub' => 'newer'];
    $this->insertLegacyRecords($records);

    $result = $this->runUpdate8204();

    $this->assertSame('newer', $this->getAuthmapRecords()['9999:openid_connect.generic']);
    $this->assertSame(0, $this->countLegacyRecords());
    $this->assertSame(self::BATCH_SIZE, $this->countAuthmapRecords());

    $this->assertSame(2, $result['passes']);
    $this->assertLessThan(1, $result['progress'][0]);
    $this->assertStringContainsString(sprintf('Moved %d record(s)', self::BATCH_SIZE + 1), $result['message']);
  }

  /**
   * Tests that a collision is retried once the identifier has been freed.
   *
   * A record can collide with an identifier that a later record then moves
   * away: here uid 1 holds 'shared-sub' until its own newer record replaces it
   * with 'own-sub'. Paging forward by 'aid' never revisits the collision, so
   * the migration sweeps again from the start until nothing more can be moved.
   *
   * @covers ::openid_connect_update_8204
   */
  public function testCollisionIsRetriedOnceIdentifierIsFreed(): void {
    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'shared-sub'],
      // Collides with uid 1 on the (authname, provider) unique key.
      ['uid' => 2, 'client_name' => 'generic', 'sub' => 'shared-sub'],
      // Moves uid 1 off 'shared-sub', which frees it for uid 2.
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'own-sub'],
    ]);

    $result = $this->runUpdate8204();

    // Both accounts keep a mapping: nothing is stranded.
    $this->assertSame([
      '1:openid_connect.generic' => 'own-sub',
      '2:openid_connect.generic' => 'shared-sub',
    ], $this->getAuthmapRecords());
    $this->assertSame(0, $this->countLegacyRecords());

    // The transient collision is not reported as an error, because it was
    // resolved, and the table is dropped as normal.
    $this->assertSame([], $this->getLoggedErrors());
    $this->assertStringContainsString('Moved 3 record(s)', $result['message']);
    $this->assertStringNotContainsString('remain in the openid_connect_authmap table', $result['message']);
    $this->assertSame('Dropped the openid_connect_authmap table.', openid_connect_update_8205());
  }

  /**
   * Tests that an unmovable record is left in the legacy table.
   *
   * The 'authmap' table has a unique key on (authname, provider), so two users
   * sharing a 'sub' for the same client cannot both be moved. The record that
   * loses must stay in 'openid_connect_authmap' instead of being deleted,
   * otherwise the mapping is destroyed for good once the table is dropped.
   *
   * @covers ::openid_connect_update_8204
   * @covers ::openid_connect_update_8205
   */
  public function testUnmovableRecordIsNotDeleted(): void {
    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'shared-sub'],
      ['uid' => 2, 'client_name' => 'generic', 'sub' => 'shared-sub'],
      ['uid' => 3, 'client_name' => 'generic', 'sub' => 'own-sub'],
    ]);

    $result = $this->runUpdate8204();

    // The first record wins, and the unrelated record is unaffected.
    $this->assertSame([
      '1:openid_connect.generic' => 'shared-sub',
      '3:openid_connect.generic' => 'own-sub',
    ], $this->getAuthmapRecords());

    // The conflicting record survives, so it can be repaired by hand.
    $leftovers = $this->database->select('openid_connect_authmap', 'a')
      ->fields('a', ['uid', 'client_name', 'sub'])
      ->execute()
      ->fetchAll();
    $this->assertCount(1, $leftovers);
    $this->assertEquals(2, $leftovers[0]->uid);
    $this->assertSame('generic', $leftovers[0]->client_name);
    $this->assertSame('shared-sub', $leftovers[0]->sub);

    // The log message carries enough context to identify the record.
    $errors = $this->getLoggedErrors();
    $this->assertCount(1, $errors);
    $this->assertEquals(2, $errors[0]['@uid']);
    $this->assertSame('generic', $errors[0]['@client']);
    $this->assertSame('shared-sub', $errors[0]['@sub']);

    // The record never reached 'authmap', so the collision is named as the
    // cause rather than a failed delete.
    $this->assertStringContainsString(
      'another account is already mapped',
      $this->getLoggedMessages()[0]
    );

    // The failure is reported to the operator rather than passing silently.
    $this->assertStringContainsString('Moved 2 record(s)', $result['message']);
    $this->assertStringContainsString('1 record(s) remain in the openid_connect_authmap table', $result['message']);

    // The legacy table is kept, because dropping it would lose the record.
    $message = openid_connect_update_8205();
    $this->assertStringContainsString('Skipped dropping', $message);
    $this->assertStringContainsString('1 record(s)', $message);
    $this->assertTrue($this->database->schema()->tableExists('openid_connect_authmap'));
  }

  /**
   * Tests that an incomplete migration is reported on the status report.
   *
   * The message returned by update 8205 only appears in the output of the
   * update itself, which is easily missed. The old table surviving is the
   * signal that 8205 did not drop it, so that is what gets reported as a
   * runtime requirement, until the table is gone.
   *
   * @covers ::openid_connect_requirements
   */
  public function testStatusReportReportsIncompleteMigration(): void {
    // Pretend the site has run every update, so that the old table surviving
    // is meaningful rather than just a pending update.
    $this->setInstalledSchemaVersion(8205);

    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'shared-sub'],
      ['uid' => 2, 'client_name' => 'generic', 'sub' => 'shared-sub'],
    ]);
    $this->runUpdate8204();
    openid_connect_update_8205();

    // The unmovable record is reported as a warning, with its count.
    $requirement = $this->getMigrationRequirement();
    $this->assertNotNull($requirement);
    $this->assertSame(REQUIREMENT_WARNING, $requirement['severity']);
    $this->assertSame('Not completed', (string) $requirement['value']);
    $this->assertStringContainsString('1 record(s) could not be moved', (string) $requirement['description']);

    // Resolving the conflict by hand leaves only the table to clean up, which
    // is reported for information rather than as a warning.
    $this->database->truncate('openid_connect_authmap')->execute();
    $requirement = $this->getMigrationRequirement();
    $this->assertNotNull($requirement);
    $this->assertSame(REQUIREMENT_INFO, $requirement['severity']);
    $this->assertSame('Clean-up pending', (string) $requirement['value']);

    // Nothing is reported once the table itself is gone.
    $this->database->schema()->dropTable('openid_connect_authmap');
    $this->assertNull($this->getMigrationRequirement());
  }

  /**
   * Tests that a site with update 8205 still pending is not reported.
   *
   * Such a site legitimately still has the old table, and core already reports
   * that it has database updates to run.
   *
   * @covers ::openid_connect_requirements
   */
  public function testStatusReportIgnoresPendingUpdates(): void {
    $this->setInstalledSchemaVersion(8204);
    $this->insertLegacyRecords([
      ['uid' => 1, 'client_name' => 'generic', 'sub' => 'sub-1'],
    ]);

    $this->assertNull($this->getMigrationRequirement());

    // The same table is reported once the update has been run.
    $this->setInstalledSchemaVersion(8205);
    $this->assertNotNull($this->getMigrationRequirement());
  }

  /**
   * Tests that records spanning several batches are all moved.
   *
   * Also covers resuming: the sandbox from the first pass is thrown away, as
   * would happen if the update died part-way through, and the records already
   * moved are not processed a second time.
   *
   * @covers ::openid_connect_update_8204
   */
  public function testRecordsAreMovedInBatches(): void {
    $total = self::BATCH_SIZE + 1;
    $records = [];
    for ($i = 1; $i <= $total; $i++) {
      $records[] = ['uid' => $i, 'client_name' => 'generic', 'sub' => 'sub-' . $i];
    }
    $this->insertLegacyRecords($records);

    // A full batch is followed by another pass. The update is not reported as
    // finished while records are still waiting.
    $sandbox = [];
    openid_connect_update_8204($sandbox);
    $this->assertLessThan(1, $sandbox['#finished']);
    $this->assertSame(self::BATCH_SIZE, $this->countAuthmapRecords());
    $this->assertSame(1, $this->countLegacyRecords());

    // Resume with a fresh sandbox, as a re-run of the update would.
    $result = $this->runUpdate8204();

    $this->assertSame($total, $this->countAuthmapRecords());
    $this->assertSame(0, $this->countLegacyRecords());
    $this->assertSame([], $this->getLoggedErrors());

    $this->assertSame(1, $result['passes']);
    $this->assertStringContainsString('Moved 1 record(s)', $result['message']);
  }

  /**
   * Tests that the update succeeds when there is nothing to move.
   *
   * @covers ::openid_connect_update_8204
   */
  public function testEmptyLegacyTable(): void {
    $result = $this->runUpdate8204();

    $this->assertSame(1, $result['passes']);
    $this->assertSame(1, $result['progress'][0]);
    $this->assertStringContainsString('Moved 0 record(s)', $result['message']);
  }

  /**
   * Runs openid_connect_update_8204() to completion.
   *
   * @return array
   *   An array with the number of passes made, the progress reported after
   *   each pass and the message returned by the final pass.
   */
  protected function runUpdate8204(): array {
    $sandbox = [];
    $passes = 0;
    $progress = [];
    $message = NULL;

    do {
      $message = openid_connect_update_8204($sandbox);
      $passes++;
      $progress[] = $sandbox['#finished'];
      $this->assertLessThan(100, $passes, 'The update finished in a sane number of passes.');
    } while ($sandbox['#finished'] < 1);

    return [
      'passes' => $passes,
      'progress' => $progress,
      'message' => (string) $message,
    ];
  }

  /**
   * Returns the message text of every logged 'openid_connect' error.
   *
   * @return string[]
   *   The untranslated messages, oldest first.
   */
  protected function getLoggedMessages(): array {
    return $this->database->select('watchdog', 'w')
      ->fields('w', ['message'])
      ->condition('w.type', 'openid_connect')
      ->condition('w.severity', RfcLogLevel::ERROR)
      ->orderBy('w.wid')
      ->execute()
      ->fetchCol();
  }

  /**
   * Returns the authmap migration requirement, if it is reported.
   *
   * @return array|null
   *   The requirement, or NULL when it is not reported.
   */
  protected function getMigrationRequirement(): ?array {
    return openid_connect_requirements('runtime')['openid_connect_authmap_migration'] ?? NULL;
  }

  /**
   * Sets the installed schema version of the module.
   */
  protected function setInstalledSchemaVersion(int $version): void {
    $this->container->get('update.update_hook_registry')
      ->setInstalledVersion('openid_connect', $version);
  }

  /**
   * Creates the legacy 'openid_connect_authmap' table.
   */
  protected function createLegacyAuthmapTable(): void {
    $this->database->schema()->createTable('openid_connect_authmap', [
      'description' => 'Stores OpenID Connect authentication mapping.',
      'fields' => [
        'aid' => [
          'description' => 'Primary Key: Unique authmap ID.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ],
        'uid' => [
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'description' => "User's {users}.uid.",
        ],
        'client_name' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'The client name.',
        ],
        'sub' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
          'description' => 'Unique subject identifier.',
        ],
      ],
      'primary key' => ['aid'],
      'indexes' => [
        'uid' => ['uid'],
        'identifier' => ['client_name', 'sub'],
      ],
    ]);
  }

  /**
   * Inserts records into the legacy table, in the given order.
   *
   * @param array[] $records
   *   Records keyed by 'uid', 'client_name' and 'sub'.
   */
  protected function insertLegacyRecords(array $records): void {
    $insert = $this->database->insert('openid_connect_authmap')
      ->fields(['uid', 'client_name', 'sub']);
    foreach ($records as $record) {
      $insert->values($record);
    }
    $insert->execute();
  }

  /**
   * Returns the 'authmap' contents keyed by "uid:provider".
   *
   * @return string[]
   *   The authname of each record, keyed by "uid:provider".
   */
  protected function getAuthmapRecords(): array {
    $rows = $this->database->select('authmap', 'a')
      ->fields('a', ['uid', 'provider', 'authname'])
      ->orderBy('a.uid')
      ->orderBy('a.provider')
      ->execute()
      ->fetchAll();

    $records = [];
    foreach ($rows as $row) {
      $records[$row->uid . ':' . $row->provider] = $row->authname;
    }
    return $records;
  }

  /**
   * Counts the records in the 'authmap' table.
   */
  protected function countAuthmapRecords(): int {
    return (int) $this->database->select('authmap')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Counts the records left in the legacy table.
   */
  protected function countLegacyRecords(): int {
    return (int) $this->database->select('openid_connect_authmap')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Returns the placeholders of every logged 'openid_connect' error.
   *
   * @return array[]
   *   The message placeholders of each error, oldest first.
   */
  protected function getLoggedErrors(): array {
    $rows = $this->database->select('watchdog', 'w')
      ->fields('w', ['variables'])
      ->condition('w.type', 'openid_connect')
      ->condition('w.severity', RfcLogLevel::ERROR)
      ->orderBy('w.wid')
      ->execute()
      ->fetchCol();

    return array_map('unserialize', $rows);
  }

}
