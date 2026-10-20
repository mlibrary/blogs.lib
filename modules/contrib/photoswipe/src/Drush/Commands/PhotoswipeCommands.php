<?php

namespace Drupal\photoswipe\Drush\Commands;

use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Filesystem\Path;

/**
 * Drush commands for the PhotoSwipe module.
 */
class PhotoswipeCommands extends DrushCommands {

  /**
   * The PhotoSwipe archive URL.
   */
  public const PHOTOSWIPE_DOWNLOAD_URL = 'https://github.com/dimsemenov/PhotoSwipe/archive/v5.4.4.zip';

  /**
   * The PhotoSwipe dynamic caption archive URL.
   */
  public const PHOTOSWIPE_DYNAMIC_CAPTION_DOWNLOAD_URL = 'https://github.com/dimsemenov/photoswipe-dynamic-caption-plugin/archive/1.2.7.zip';

  /**
   * The PhotoSwipe library directory name.
   */
  public const PHOTOSWIPE_LIBRARY_NAME = 'photoswipe';

  /**
   * The PhotoSwipe dynamic caption library directory name.
   */
  public const PHOTOSWIPE_DYNAMIC_CAPTION_LIBRARY_NAME = 'photoswipe-dynamic-caption-plugin';

  /**
   * Constructs a PhotoswipeCommands object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   */
  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
  }

  /**
   * Downloads and installs the PhotoSwipe library.
   */
  #[CLI\Command(name: 'photoswipe:install-plugin', aliases: ['photoswipe-install-plugin'])]
  #[CLI\Usage(name: 'drush photoswipe:install-plugin', description: 'Download the PhotoSwipe library into the Drupal libraries directory.')]
  #[CLI\Bootstrap(level: DrupalBootLevels::ROOT)]
  public function installPlugin(): void {
    $libraryDirectory = Path::join(DRUPAL_ROOT, 'libraries');
    $targetDirectory = Path::join($libraryDirectory, self::PHOTOSWIPE_LIBRARY_NAME);
    $dynamicCaptionTargetDirectory = Path::join($libraryDirectory, self::PHOTOSWIPE_DYNAMIC_CAPTION_LIBRARY_NAME);

    if (!$this->fileSystem->prepareDirectory($libraryDirectory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS)) {
      throw new \RuntimeException(dt('Unable to prepare the directory @path.', ['@path' => $libraryDirectory]));
    }

    if (file_exists($targetDirectory)) {
      throw new \RuntimeException(dt('The path @path already exists and needs to be deleted first, if this should be re-downloaded.', [
        '@path' => $targetDirectory,
      ]));
    }

    $installDynamicCaption = $this->io()->confirm(
      dt('Also download the PhotoSwipe dynamic caption plugin for the photoswipe_dynamic_caption submodule?'),
      FALSE,
    );

    if ($installDynamicCaption && file_exists($dynamicCaptionTargetDirectory)) {
      throw new \RuntimeException(dt('The path @path already exists and needs to be deleted first, if this should be re-downloaded.', [
        '@path' => $dynamicCaptionTargetDirectory,
      ]));
    }

    $this->installLibraryFromArchive(
      self::PHOTOSWIPE_DOWNLOAD_URL,
      $targetDirectory,
      dt('PhotoSwipe'),
    );

    if ($installDynamicCaption) {
      $this->installLibraryFromArchive(
        self::PHOTOSWIPE_DYNAMIC_CAPTION_DOWNLOAD_URL,
        $dynamicCaptionTargetDirectory,
        dt('PhotoSwipe dynamic caption plugin'),
      );
    }
  }

  /**
   * Downloads, extracts, and installs a library from an archive URL.
   *
   * @param string $downloadUrl
   *   The archive download URL.
   * @param string $targetDirectory
   *   The target library directory.
   * @param string $label
   *   The human-readable library label for status messages.
   */
  private function installLibraryFromArchive(string $downloadUrl, string $targetDirectory, string $label): void {
    $archivePath = $this->fileSystem->tempnam(sys_get_temp_dir(), 'photoswipe_');
    if ($archivePath === FALSE) {
      throw new \RuntimeException(dt('Unable to create a temporary file for the @label download.', ['@label' => $label]));
    }

    $extractDirectory = Path::join(sys_get_temp_dir(), uniqid('photoswipe_extract_', TRUE));

    try {
      if (!$this->fileSystem->mkdir($extractDirectory)) {
        throw new \RuntimeException(dt('Unable to create the temporary directory @path.', ['@path' => $extractDirectory]));
      }

      try {
        $this->httpClient->request('GET', $downloadUrl, [
          'sink' => $archivePath,
          'connect_timeout' => 10,
          'timeout' => 60,
        ]);
      }
      catch (GuzzleException $e) {
        throw new \RuntimeException(dt('Unable to download the @label library from @url: @message', [
          '@label' => $label,
          '@url' => $downloadUrl,
          '@message' => $e->getMessage(),
        ]), 0, $e);
      }

      // Extract the archive, and copy it to the target directory. A plain
      // move is not used here because the extraction directory and the
      // target directory can reside on different filesystems/mounts, and
      // PHP's rename() (which move() relies on) cannot move directories
      // across filesystem boundaries.
      $extractPath = $this->extractArchive($archivePath, $extractDirectory, $label);
      $this->copyDirectory($extractPath, $targetDirectory);

      $this->logger()->success(dt('@label has been installed in %path (from %source).', [
        '@label' => $label,
        '%source' => $downloadUrl,
        '%path' => $targetDirectory,
      ]));
    }
    // If anything goes wrong, or we successfully finished, remove the temporary
    // file directories:
    finally {
      if (file_exists($archivePath)) {
        $this->fileSystem->deleteRecursive($archivePath);
      }
      if (is_dir($extractDirectory)) {
        $this->fileSystem->deleteRecursive($extractDirectory);
      }
    }
  }

  /**
   * Extracts the archive and returns the extracted library directory.
   *
   * @param string $archivePath
   *   The archive path.
   * @param string $extractDirectory
   *   The extraction directory.
   * @param string $label
   *   The human-readable library label for status messages.
   *
   * @return string
   *   The extracted library directory.
   */
  private function extractArchive(string $archivePath, string $extractDirectory, string $label): string {
    if (!class_exists(\ZipArchive::class)) {
      throw new \RuntimeException(dt('The PHP zip extension is required to install the @label library.', ['@label' => $label]));
    }

    $zip = new \ZipArchive();
    if ($zip->open($archivePath) !== TRUE) {
      throw new \RuntimeException(dt('Unable to open the downloaded @label archive.', ['@label' => $label]));
    }

    if (!$zip->extractTo($extractDirectory)) {
      $zip->close();
      throw new \RuntimeException(dt('Unable to extract the downloaded @label archive.', ['@label' => $label]));
    }
    $zip->close();

    $entries = array_values(array_filter(
      scandir($extractDirectory) ?: [],
      static fn (string $entry): bool => !in_array($entry, ['.', '..'], TRUE),
    ));

    if (count($entries) !== 1 || !is_dir($sourceDirectory = Path::join($extractDirectory, $entries[0]))) {
      throw new \RuntimeException(dt('Unable to locate the extracted @label directory.', ['@label' => $label]));
    }

    return $sourceDirectory;
  }

  /**
   * Recursively copies a directory's contents into a new target directory.
   *
   * @param string $source
   *   The source directory.
   * @param string $target
   *   The target directory. Must not already exist.
   */
  private function copyDirectory(string $source, string $target): void {
    if (!$this->fileSystem->mkdir($target)) {
      throw new \RuntimeException(dt('Unable to create the directory @path.', ['@path' => $target]));
    }

    $entries = array_filter(
      scandir($source) ?: [],
      static fn (string $entry): bool => !in_array($entry, ['.', '..'], TRUE),
    );

    foreach ($entries as $entry) {
      $sourcePath = Path::join($source, $entry);
      $targetPath = Path::join($target, $entry);
      if (is_dir($sourcePath)) {
        $this->copyDirectory($sourcePath, $targetPath);
      }
      elseif ($this->fileSystem->copy($sourcePath, $targetPath, FileExists::Error) === FALSE) {
        throw new \RuntimeException(dt('Unable to copy @source to @target.', [
          '@source' => $sourcePath,
          '@target' => $targetPath,
        ]));
      }
    }
  }

}
