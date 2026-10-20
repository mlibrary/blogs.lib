<?php

namespace Drupal\Tests\photoswipe\Kernel;

use Drupal\Core\File\FileSystemInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\photoswipe\Drush\Commands\PhotoswipeCommands;
use Drush\Log\DrushLoggerManager;
use Drush\Style\DrushStyle;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// Drush's dt() function is a plain include, not autoloaded by Composer:
require_once DRUPAL_ROOT . '/../vendor/drush/drush/includes/output.inc';

/**
 * Tests the Photoswipe Drush commands.
 */
#[CoversClass(PhotoswipeCommands::class)]
#[Group('photoswipe')]
#[RunTestsInSeparateProcesses]
class PhotoswipeCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['photoswipe'];

  /**
   * Tests the plugin download command without the dynamic caption library.
   */
  #[Group('legacy')]
  public function testInstallPluginCommandWithoutDynamicCaption(): void {
    $fileSystem = $this->container->get('file_system');
    $this->prepareLibrariesDirectory($fileSystem);

    $httpClient = $this->createHttpClientMock(1);
    $command = $this->createInstallPluginCommand($httpClient, $fileSystem, FALSE);

    $photoswipeDirectory = DRUPAL_ROOT . '/libraries/photoswipe';
    $dynamicCaptionDirectory = DRUPAL_ROOT . '/libraries/photoswipe-dynamic-caption-plugin';

    try {
      $command->installPlugin();

      $this->assertDirectoryExists($photoswipeDirectory);
      $this->assertFileExists($photoswipeDirectory . '/test.txt');
      $this->assertDirectoryDoesNotExist($dynamicCaptionDirectory);

      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage('already exists and needs to be deleted first');

      $command->installPlugin();
    }
    finally {
      $this->cleanupInstalledLibraries($fileSystem);
    }
  }

  /**
   * Tests the plugin download command with the dynamic caption library.
   */
  #[Group('legacy')]
  public function testInstallPluginCommandWithDynamicCaption(): void {
    $fileSystem = $this->container->get('file_system');
    $this->prepareLibrariesDirectory($fileSystem);

    $httpClient = $this->createHttpClientMock(2);
    $command = $this->createInstallPluginCommand($httpClient, $fileSystem, TRUE);

    $photoswipeDirectory = DRUPAL_ROOT . '/libraries/photoswipe';
    $dynamicCaptionDirectory = DRUPAL_ROOT . '/libraries/photoswipe-dynamic-caption-plugin';

    try {
      $command->installPlugin();

      $this->assertDirectoryExists($photoswipeDirectory);
      $this->assertFileExists($photoswipeDirectory . '/test.txt');
      $this->assertDirectoryExists($dynamicCaptionDirectory);
      $this->assertFileExists($dynamicCaptionDirectory . '/test.txt');

      $this->expectException(\RuntimeException::class);
      $this->expectExceptionMessage('already exists and needs to be deleted first');

      $command->installPlugin();
    }
    finally {
      $this->cleanupInstalledLibraries($fileSystem);
    }
  }

  /**
   * Prepares the libraries directory for installation tests.
   */
  private function prepareLibrariesDirectory(FileSystemInterface $fileSystem): void {
    $directory = DRUPAL_ROOT . '/libraries';
    $fileSystem->prepareDirectory(
      $directory,
      FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS,
    );
  }

  /**
   * Removes installed library directories after a test.
   */
  private function cleanupInstalledLibraries(FileSystemInterface $fileSystem): void {
    foreach ([
      DRUPAL_ROOT . '/libraries/photoswipe',
      DRUPAL_ROOT . '/libraries/photoswipe-dynamic-caption-plugin',
    ] as $directory) {
      if (is_dir($directory)) {
        $fileSystem->deleteRecursive($directory);
      }
    }
  }

  /**
   * Creates a mocked HTTP client that serves dummy library archives.
   */
  private function createHttpClientMock(int $expectedRequestCount): ClientInterface {
    $httpClient = $this->createMock(ClientInterface::class);
    $httpClient->expects($this->exactly($expectedRequestCount))
      ->method('request')
      ->willReturnCallback(function ($method, $uri, $options) {
        if (file_exists($options['sink'])) {
          unlink($options['sink']);
        }

        $rootDirectory = str_contains($uri, 'dynamic-caption')
          ? PhotoswipeCommands::PHOTOSWIPE_DYNAMIC_CAPTION_LIBRARY_NAME
          : PhotoswipeCommands::PHOTOSWIPE_LIBRARY_NAME;

        $zip = new \ZipArchive();
        $zip->open($options['sink'], \ZipArchive::CREATE);
        $zip->addFromString($rootDirectory . '/test.txt', 'dummy content');
        $zip->close();

        return new Response(200);
      });

    return $httpClient;
  }

  /**
   * Creates the install plugin command with a mocked confirmation prompt.
   */
  private function createInstallPluginCommand(
    ClientInterface $httpClient,
    FileSystemInterface $fileSystem,
    bool $installDynamicCaption,
  ): PhotoswipeCommands {
    $command = $this->getMockBuilder(PhotoswipeCommands::class)
      ->setConstructorArgs([$httpClient, $fileSystem])
      ->onlyMethods(['io'])
      ->getMock();

    $io = $this->createMock(DrushStyle::class);
    $io->expects($this->once())
      ->method('confirm')
      ->willReturn($installDynamicCaption);
    $command->method('io')->willReturn($io);

    $logger = $this->createMock(DrushLoggerManager::class);
    $command->setLogger($logger);

    return $command;
  }

}
