<?php

namespace derhasi\Composer\Tests;

use derhasi\Composer\PathPreserver;
use Composer\Util\Filesystem;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Composer;
use Composer\Config;
use derhasi\tempdirectory\TempDirectory;

require_once __DIR__ . '/../vendor/derhasi/tempdirectory/TempDirectory.php';

class PathPreserverTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \derhasi\tempdirectory\TempDirectory
   */
  private $workingDirectory;

  /**
   * @var \derhasi\tempdirectory\TempDirectory
   */
  private $cacheDirectory;

  /**
   * set up test environmemt
   */
  public function setUp() {
    $this->fs = new Filesystem();
    $this->io = $this->getMock('Composer\IO\IOInterface');
    $this->workingDirectory = new TempDirectory('path-preserver-test-working');
    $this->cacheDirectory = new TempDirectory('path-preserver-test-cache');
  }

  /**
   * test that the directory is created
   */
  public function testPreserveAndRollback() {

    // Create directory to test.
    $folder1 = $this->workingDirectory->getPath('folder1');
    mkdir($folder1);
    $file1 = $this->workingDirectory->getPath('file1.txt');
    file_put_contents($file1, 'Test content');

    // We simulate creation of
    $installPaths = array(
      $this->workingDirectory->getRoot()
    );

    $preservePaths = array(
      $folder1,
      $file1,
    );

    $preserver = new PathPreserver($installPaths, $preservePaths, $this->cacheDirectory->getRoot(), $this->fs, $this->io);
    $this->assertTrue(file_exists($folder1) && is_dir($folder1), 'Folder created.');
    $this->assertTrue(file_exists($file1), 'File created.');

    $preserver->preserve();
    $this->assertFalse(file_exists($folder1), 'Folder removed for backup.');
    $this->assertFalse(file_exists($file1), 'File was removed for backup.');

    $preserver->rollback();
    $this->assertTrue(file_exists($folder1) && is_dir($folder1), 'Folder recreated.');
    $this->assertTrue(file_exists($file1), 'File recreated.');
  }

  public function testFileModes() {

  }

}