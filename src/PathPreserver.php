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
   * @var string[]
   */
  protected $installPaths;

  /**
   * @var string[]
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
   * @param string[] $installPaths
   *   Array of install paths (must be absolute)
   * @param string[] $preservePaths
   *   Array of preservable paths (must be absolute)
   * @param string $cacheDir
   *   Absolute path to composer cache dir.
   * @param \Composer\Util\FileSystem $filesystem
   *   The filesystem provided by composer to work with.
   */
  public function __construct($installPaths, $preservePaths, $cacheDir, $filesystem) {
    $this->installPaths = array_unique($installPaths);
    $this->preservePaths = array_unique($preservePaths);
    $this->filesystem = $filesystem;
    $this->cacheDir = $cacheDir;
  }


  /**
   * Backs up the paths.
   */
  public function preserve() {

    foreach ($this->installPaths as $installPath) {
      $installPathNormalized = $this->filesystem->normalizePath($installPath);

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
        continue;
      }

      $unique = $installPath . ' ' . time();
      $cache_root = $this->filesystem->normalizePath($this->cacheDir . '/preserve-paths/' . sha1($unique));
      $this->filesystem->ensureDirectoryExists($cache_root);

      foreach ($backup_paths as $original) {
        $backup_location = $cache_root . '/' . sha1($original);
        $this->filesystem->rename($original, $backup_location);
        $this->backups[$original] = $backup_location;
      }
    }
  }

  /**
   * Restore previously backed up paths.
   *
   * @see PathPreserver::backupSubpaths()
   */
  public function rollback() {
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
