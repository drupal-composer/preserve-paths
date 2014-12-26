<?php

/**
 * Contains \derhasi\Composer\PathPreserver
 */

namespace derhasi\Composer;

/**
 * Class PathPreserver
 */
class PathPreserver {

  /**
   * @var string
   */
  protected $cacheDir;

  /**
   * @var string
   */
  protected $installPath;

  /**
   * @var array
   */
  protected $preservePaths;

  /**
   * @var \Composer\Util\FileSystem
   */
  protected $filesystem;

  /**
   * @var array
   */
  protected $backups = array();

  /**
   * Constructor.
   *
   * @param string $installPath
   * @param array $preservePaths
   * @param string $cacheDir
   * @param \Composer\Util\FileSystem $filesystem
   */
  public function __construct($installPath, $preservePaths, $cacheDir, $filesystem) {
    $this->installPath = $installPath;
    $this->preservePaths = $preservePaths;
    $this->filesystem = $filesystem;
    $this->cacheDir = $cacheDir;
  }


  /**
   * Backs up the paths.
   */
  protected function preserve() {

    $installPathNormalized = $this->filesystem->normalizePath($this->installPath);

    // Check if any path may be affected by modifying the install path.
    $backup_paths = array();
    foreach ($this->preservePaths as $path) {
      $normalizedPath = $this->filesystem->normalizePath($path);
      if (file_exists($path) && strpos($normalizedPath, $installPathNormalized) === 0) {
        $backup_paths[] = $normalizedPath;
      }
    }

    // If no paths need to be backed up, we simply proceed.
    if (empty($backup_paths)) {
      return;
    }

    $unique = $this->installPath . ' ' . time();
    $cache_root = $this->filesystem->normalizePath($this->cacheDir . '/preserve-paths/' . sha1($unique));
    $this->filesystem->ensureDirectoryExists($cache_root);

    foreach ($backup_paths as $original) {
      $backup_location = $cache_root . '/' . sha1($original);
      $this->filesystem->rename($original, $backup_location);
      $this->backups[$original] = $backup_location;
    }
  }

  /**
   * Restore previously backed up paths.
   *
   * @see PathPreserver::backupSubpaths()
   */
  protected function rollback() {
    if (empty($this->backups)) {
      return;
    }

    foreach ($this->backups as $original => $backup_location) {

      // Remove any code that was placed by the package at the place of
      // the original path.
      if (file_exists($original)) {
        if (is_dir($original)) {
          $this->filesystem->emptyDirectory($original, false);
          $this->filesystem->removeDirectory($original);
        }
        else {
          $this->filesystem->remove($original);
        }

        // @todo: provide messages to io
        //$this->io->write(sprintf('<comment>Content of package %s was overwritten with preserved path %s!</comment>', $package->getUniqueName(), $original), true);
      }

      $this->filesystem->ensureDirectoryExists(dirname($original));
      $this->filesystem->rename($backup_location, $original);

      if ($this->filesystem->isDirEmpty(dirname($backup_location))) {
        $this->filesystem->removeDirectory(dirname($backup_location));
      }
    }

    // With a clean array, we can start over.
    $this->backups = array();
  }

}
