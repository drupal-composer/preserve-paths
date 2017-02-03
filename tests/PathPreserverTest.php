<?php

namespace deminy\Composer\Tests;

use Composer\IO\IOInterface;
use Composer\Util\Filesystem;
use deminy\Composer\PathPreserver;
use derhasi\tempdirectory\TempDirectory;
use PHPUnit_Framework_TestCase;

class PathPreserverTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var IOInterface
     */
    protected $io;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var TempDirectory
     */
    protected $workingDirectory;

    /**
     * @var TempDirectory
     */
    protected $cacheDirectory;

    /**
     * set up test environment
     */
    public function setUp()
    {
        $this->io = $this->createMock(IOInterface::class);
        $this->fs = new Filesystem();

        $prefixRootDir          = 'test-' . uniqid();
        $this->workingDirectory = new TempDirectory($prefixRootDir);
        $this->cacheDirectory   = new TempDirectory($prefixRootDir . '-cache');
    }

    /**
     * Tests that the directory is created
     */
    public function testPreserveAndRollback()
    {
        $existingFolders = array_fill_keys(
            array(
                'existingFolderTypeA1', // An existing folder that matches wildcard pattern and should be preserved
                'existingFolderTypeA2', // An existing folder that matches wildcard pattern and should be preserved
                'existingFolderTypeB3', // An existing folder that not match wildcard pattern and should not be removed
            ),
            null
        );
        foreach ($existingFolders as $folder => $null) {
            $existingFolders[$folder] = $this->workingDirectory->getPath($folder);
            mkdir($existingFolders[$folder]);
        }
        $nonExistingFolders = array_map(
            function ($folder) {
                return $this->workingDirectory->getPath($folder);
            },
            array('nonExistingFolder1', 'nonExistingFolder2', 'nonExistingFolder3')
        );
        $file1 = $this->workingDirectory->getPath('file1.txt');
        file_put_contents($file1, 'Test content');

        $preserver = new PathPreserver(
            array(
                $this->workingDirectory->getRoot()
            ),
            array(
                $this->workingDirectory->getRoot() . DIRECTORY_SEPARATOR . 'existingFolderTypeA*',
                $this->workingDirectory->getRoot() . DIRECTORY_SEPARATOR . 'nonExistingFolder1',
                $this->workingDirectory->getRoot() . DIRECTORY_SEPARATOR . 'file1.txt',
            ),
            $this->cacheDirectory->getRoot(),
            $this->fs,
            $this->io
        );

        $this->assertIsDir($existingFolders['existingFolderTypeA1'], 'Folder created.');
        $this->assertIsDir($existingFolders['existingFolderTypeA2'], 'Folder created.');
        $this->assertIsDir($existingFolders['existingFolderTypeB3'], 'Folder created.');
        foreach ($nonExistingFolders as $folder) {
            $this->assertFileNotExists($folder, 'Folder not exist.');
        }
        $this->assertFileExists($file1, 'File created.');

        $preserver->preserve();
        $this->assertFileNotExists($existingFolders['existingFolderTypeA1'], 'Folder removed for backup.');
        $this->assertFileNotExists($existingFolders['existingFolderTypeA2'], 'Folder removed for backup.');
        $this->assertIsDir($existingFolders['existingFolderTypeB3'], 'Folder still there.');
        foreach ($nonExistingFolders as $folder) {
            $this->assertFileNotExists($folder, 'Folder not exist.');
        }
        $this->assertFileNotExists($file1, 'File was removed for backup.');

        $preserver->rollback();
        $this->assertIsDir($existingFolders['existingFolderTypeA1'], 'Folder created.');
        $this->assertIsDir($existingFolders['existingFolderTypeA2'], 'Folder created.');
        $this->assertIsDir($existingFolders['existingFolderTypeB3'], 'Folder still there.');
        foreach ($nonExistingFolders as $folder) {
            $this->assertFileNotExists($folder, 'Folder not exist.');
        }
        $this->assertFileExists($file1, 'File recreated.');
    }

    /**
     * Tests file_exists() restrictions on non executable directories.
     */
    public function testFileExists()
    {
        $folder1 = $this->workingDirectory->getPath('folder1');
        $subfolder1 = $this->workingDirectory->getPath('folder1/subfolder1');
        $file1 = $this->workingDirectory->getPath('folder1/subfolder1/file1.txt');
        $file2 = $this->workingDirectory->getPath('folder1/file2.txt');

        mkdir($folder1);
        mkdir($subfolder1);
        file_put_contents($file1, '');
        file_put_contents($file2, '');

        $this->assertIsDir($folder1);
        $this->assertIsDir($subfolder1);
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        // After chaning the file mode of the parent directory, no containing files
        // or folders can be found anymore.
        chmod($folder1, 0400);

        $this->assertTrue(file_exists($folder1), 'Folder is still present.');
        $this->assertFalse(file_exists($subfolder1), 'File exists retures FALSE for subfolder');
        $this->assertFalse(file_exists($file1), 'File exists retures FALSE for subfolder');
        $this->assertFalse(file_exists($file2), 'File exists retures FALSE for subfolder');
    }

    /**
     * Tests preservation and rollback on tricky path permissions.
     *
     * @depends testPreserveAndRollback
     */
    public function testFileModes()
    {
        // Create directory to test.
        $folder1 = $this->workingDirectory->getPath('folder1');
        mkdir($folder1);

        $subfolder1 = $this->workingDirectory->getPath('folder1/subfolder1');
        mkdir($subfolder1);

        $file1 = $this->workingDirectory->getPath('folder1/file1.txt');
        file_put_contents($file1, 'Test content');
        $file2 = $this->workingDirectory->getPath('folder1/file2.txt');
        file_put_contents($file2, 'Test content 2');

        // After changing some permissions we test if the given paths can be
        // restored.
        chmod($file2, 0400);
        // For checking if file exists, we need to set the permission for the folder
        // differently
        // @see http://stackoverflow.com/questions/11834629/glob-lists-files-file-exists-says-they-dont-exist
        chmod($folder1, 0500);

        $this->assertIsDir($folder1, 'Folder created.');
        $this->assertIsDir($subfolder1, 'Subfolder 1 created.');
        $this->assertFileExists($file1, 'File 1 created.');
        $this->assertFileExists($file2, 'File 2 created.');

        $preserver = new PathPreserver(
            array(
                $folder1,
            ),
            array(
                $subfolder1,
                $file1,
                $file2,
            ),
            $this->cacheDirectory->getRoot(),
            $this->fs,
            $this->io
        );

        // We check if preservation works even with restrictive permissions.
        chmod($folder1, 0400);
        $preserver->preserve();
        chmod($folder1, 0500);

        $this->assertIsDir($folder1, 'Folder not removed for backup.');
        $this->assertFileNotExists($subfolder1, 'Subfolder removed for backup.');
        $this->assertFileNotExists($file1, 'File 1 was removed for backup.');
        $this->assertFileNotExists($file2, 'File 2 was removed for backup.');

        // We check if rollback works even with restrictive permissions.
        chmod($folder1, 0400);
        $preserver->rollback();
        chmod($folder1, 0500);

        $this->assertFileExists($subfolder1, 'Subfolder 1 recreated.');
        $this->assertFileExists($file1, 'File 1 recreated.');
        $this->assertFileExists($file2, 'File 2 recreated.');
    }

    /**
     * Custom assertion for existing directory.
     *
     * @param $path
     * @param string $message
     */
    protected function assertIsDir($path, $message = '')
    {
        $this->assertTrue(file_exists($path) && is_dir($path), $message);
    }
}
