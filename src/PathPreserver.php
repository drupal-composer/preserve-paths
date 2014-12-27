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
   * Temporary file permission to allow moving protected paths.
   */
  const FILEPERM = 0755;

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
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var string[string]
   */
  protected $backups = array();

  /**
   * @var string[string]
   */
  protected $filepermissions = array();

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
   * @param \Composer\IO\IOInterface $io
   *   IO interface for writing messages.
   */
  public function __construct($installPaths, $preservePaths, $cacheDir, \Composer\Util\FileSystem $filesystem, \Composer\IO\IOInterface $io) {
    $this->installPaths = array_unique($installPaths);
    $this->preservePaths = array_unique($preservePaths);
    $this->filesystem = $filesystem;
    $this->cacheDir = $cacheDir;
    $this->io = $io;
  }

  /**
   * Backs up the paths.
   */
  public function preserve() {

    foreach ($this->installPaths as $installPath) {
      $installPathNormalized = $this->filesystem->normalizePath($installPath);

      // Check if any path may be affected by modifying the install path.
      $relevant_paths = array();
      foreach ($this->preservePaths as $path) {
        $normalizedPath = $this->filesystem->normalizePath($path);
        if (file_exists($path) && strpos($normalizedPath, $installPathNormalized) === 0) {
          $relevant_paths[] = $normalizedPath;
        }
      }

      // If no paths need to be backed up, we simply proceed.
      if (empty($relevant_paths)) {
        continue;
      }

      $unique = $installPath . ' ' . time();
      $cache_root = $this->filesystem->normalizePath($this->cacheDir . '/preserve-paths/' . sha1($unique));
      $this->filesystem->ensureDirectoryExists($cache_root);

      // Before we back paths up, we need to make sure, permissions are
      // sufficient to that task.
      $this->preparePathPermissions($relevant_paths);

      foreach ($relevant_paths as $original) {
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

        $this->io->write(sprintf('<comment>Files of installed package were overwritten with preserved path %s!</comment>', $original), true);
      }

      $this->filesystem->ensureDirectoryExists(dirname($original));
      $this->filesystem->rename($backup_location, $original);

      if ($this->filesystem->isDirEmpty(dirname($backup_location))) {
        $this->filesystem->removeDirectory(dirname($backup_location));
      }
    }

    $this->restorePathPermissions();

    // With a clean array, we can start over.
    $this->backups = array();
  }

  /**
   * Prepares source paths for backup.
   *
   * @param $paths
   *
   * @see PathPreserver::restorePathPermissions()
   */
  protected function preparePathPermissions($paths) {
    foreach ($paths as $path) {
      // In the case the path or its parent is not writable, we cannot move the
      // path. Therefore we change the permissions temporarily and restore them
      // later.
      if (!is_writable($path)) {
        $this->makePathWritable($path);
      }

      $parent = dirname($path);
      if (!is_writable($path)) {
        $this->makePathWritable($parent);
      }
    }
  }

  /**
   * Helper to make path writable.
   *
   * @param string $path
   */
  protected function makePathWritable($path) {
    $this->filepermissions[$path] = fileperms($path);
    chmod($path, static::FILEPERM);
  }

  /**
   * Restores path permissions that have been changed before.
   *
   * @see PathPreserver::preparePathPermissions()
   */
  protected function restorePathPermissions() {
    // We need to restore permissions from parent to child.
    sort($this->filepermissions);

    foreach ($this->filepermissions as $path => $perm) {
      chmod($path, $perm);
    }
  }

}
