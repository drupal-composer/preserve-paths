<?php

/**
 * @file
 * Contains derhasi\Composer\Plugin.
 */

namespace derhasi\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\PackageEvent;
use Composer\Script\ScriptEvents;
use Composer\Util\Filesystem;

/**
 * Class Plugin.
 */
class Plugin implements PluginInterface, EventSubscriberInterface {

  /**
   * @var \Composer\IO\IOInterface
   */
  protected $io;

  /**
   * @var \Composer\Composer
   */
  protected $composer;

  /**
   * @var \Composer\Util\Filesystem
   */
  protected $filesystem;

  /**
   * @var \derhasi\Composer\PathPreserver[string]
   */
  protected $preservers;

  /**
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $this->composer = $composer;
    $this->filesystem = new Filesystem();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      ScriptEvents::PRE_PACKAGE_INSTALL => 'prePackage',
      ScriptEvents::POST_PACKAGE_INSTALL => 'postPackage',
      ScriptEvents::PRE_PACKAGE_UPDATE => 'prePackage',
      ScriptEvents::POST_PACKAGE_UPDATE => 'postPackage',
      ScriptEvents::PRE_PACKAGE_UNINSTALL => 'prePackage',
      ScriptEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
    );
  }

  /**
   * Pre Package event behaviour for backing up preserved paths.
   *
   * @param \Composer\Script\PackageEvent $event
   */
  public function prePackage(PackageEvent $event) {

    $packages = $this->getPackagesFromEvent($event);
    $paths = $this->getInstallPathsFromPackages($packages);

    $preserver = new PathPreserver(
      $paths,
      $this->getPreservePaths(),
      $this->composer->getConfig()->get('cache-dir'),
      $this->filesystem,
      $this->io
    );

    // Store preserver for reuse in post package.
    $this->preservers[$this->getUniqueNameFromPackages($packages)] = $preserver;

    $preserver->preserve();
  }

  /**
   * Pre Package event behaviour for backing up preserved paths.
   *
   * @param \Composer\Script\PackageEvent $event
   */
  public function postPackage(PackageEvent $event) {
    $packages = $this->getPackagesFromEvent($event);
    $key = $this->getUniqueNameFromPackages($packages);
    if ($this->preservers[$key]) {
      $this->preservers[$key]->rollback();
      unset($this->preservers[$key]);
    }
  }

  /**
   * Retrieves relevant package from the event.
   *
   * In the case of update, the target package is retrieved, as that will
   * provide the path the package will be installed to.
   *
   * @param \Composer\Script\PackageEvent $event
   * @return \Composer\Package\PackageInterface[]
   * @throws \Exception
   */
  protected function getPackagesFromEvent(PackageEvent $event) {

    $operation = $event->getOperation();
    if ($operation instanceof InstallOperation) {
      $packages = array($operation->getPackage());
    }
    elseif ($operation instanceof UpdateOperation) {
      $packages = array(
        $operation->getInitialPackage(),
        $operation->getTargetPackage(),
      );
    }
    elseif ($operation instanceof UninstallOperation) {
      $packages = array($operation->getPackage());
    }

    return $packages;
  }

  /**
   * Retrieve install paths from package installers.
   *
   * @param \Composer\Package\PackageInterface[] $packages
   *
   * @return string[]
   */
  protected function getInstallPathsFromPackages(array $packages) {
    /** @var \Composer\Installer\InstallationManager $installationManager */
    $installationManager = $this->composer->getInstallationManager();

    $paths = array();
    foreach ($packages as $package) {
      $paths[] = $installationManager->getInstallPath($package);
    }
    return $this->absolutePaths($paths);
  }

  /**
   * Provides a unique string for a package combination.
   *
   * @param \Composer\Package\PackageInterface[] $packages
   *
   * @return string
   */
  protected function getUniqueNameFromPackages(array $packages) {
    $return = array();
    foreach ($packages as $package) {
      $return[] = $package->getUniqueName();
    }
    sort($return);
    return implode(', ', $return);
  }

  /**
   * Get preserve paths from root configuration.
   *
   * @return string[]
   */
  protected function getPreservePaths() {
    $extra = $this->composer->getPackage()->getExtra();

    if (!isset($extra['preserve-paths'])) {
      $paths = $extra['preserve-paths'];
    }
    elseif (!is_array($extra['preserve-paths']) && !is_object($extra['preserve-paths'])) {
      $paths = array($extra['preserve-paths']);
    }
    else {
      $paths = array_values((array) $extra['preserve-paths']);
    }

    return $this->absolutePaths($paths);
  }

  /**
   * Helper to convert relative paths to absolute ones.
   *
   * @param string[] $paths
   * @return string[]
   */
  protected function absolutePaths($paths) {
    $return = array();
    foreach ($paths as $path) {

      if (!$this->filesystem->isAbsolutePath($path)) {
        $path = getcwd() . '/' . $path;
      }
      $return[] = $path;
    }
    return $return;
  }
}
