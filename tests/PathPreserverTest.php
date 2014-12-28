<?php

namespace derhasi\Composer\Tests;

use derhasi\Composer\PathPreserver;

use Composer\Util\Filesystem;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Composer;
use Composer\Config;
use org\bovigo\vfs\vfsStream;


class PathPreserverTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var \org\bovigo\vfs\vfsStream
   */
  private $root;

  /**
   * set up test environmemt
   */
  public function setUp() {
    $this->fs = new Filesystem();
    $this->io = $this->getMock('Composer\IO\IOInterface');
  }

  /**
   * test that the directory is created
   */
  public function testPreserveAndRollback() {

    $cacheDir = vfsStream::setup('cache');
    $workingDir = vfsStream::setup('working');

    $workingStructure = array(
      'parentA' => array(
        'childA' => array(
          'file1.txt' => 'This is file 1',
          'file2.txt' => 'This is file 2',
        ),
      ),
    );
    vfsStream::create($workingStructure, $workingDir);

    $workingDirPath = $workingDir->url();

    // We simulate creation of
    $installPaths = array(
      $workingDirPath . '/parentA'
    );

    $preservePaths = array(
      $workingDirPath . '/parentA/childA',
      $workingDirPath . '/parentA/childB',
    );

    $preserver = new PathPreserver($installPaths, $preservePaths, $cacheDir->path(), $this->fs, $this->io);
    $this->assertTrue(file_exists($workingDirPath . '/parentA/childA/file1.txt'), 'File structure was created.');

    $preserver->preserve();
    $this->assertFalse(file_exists($workingDirPath . '/parentA/childA'), 'Path was removed for backup.');

    $preserver->rollback();
    $this->assertTrue(file_exists($workingDirPath . '/parentA/childA/file1.txt'), 'Path was recreated.');
  }
}