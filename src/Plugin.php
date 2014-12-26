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
use Composer\Package\PackageInterface;
use Composer\Plugin\PluginEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\PackageEvent;
use Composer\Script\ScriptEvents;

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
   * {@inheritdoc}
   */
  public function activate(Composer $composer, IOInterface $io) {
    $this->io = $io;
    $this->composer = $composer;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return array(
      PluginEvents::PRE_FILE_DOWNLOAD => 'logEvent',
      PluginEvents::COMMAND => 'logEvent',
      ScriptEvents::PRE_PACKAGE_INSTALL => 'logPackageEvent',
      ScriptEvents::POST_PACKAGE_INSTALL => 'logPackageEvent',
      ScriptEvents::PRE_PACKAGE_UPDATE => 'logPackageEvent',
      ScriptEvents::POST_PACKAGE_UPDATE => 'logPackageEvent',
      ScriptEvents::PRE_PACKAGE_UNINSTALL => 'logPackageEvent',
      ScriptEvents::POST_PACKAGE_UNINSTALL => 'logPackageEvent',
    );
  }

  /**
   * Simply log event call.
   *
   * @param \Composer\EventDispatcher\Event $event
   */
  public function logEvent($event) {
    $this->io->write(sprintf('Event called: %s', $event->getName()), TRUE);
  }

  public function logPackageEvent($event) {
    if ($event instanceof PackageEvent) {

      $operation = $event->getOperation();
      if ($operation instanceof InstallOperation) {
        $package = $operation->getPackage();
      }
      elseif ($operation instanceof UpdateOperation) {
        $package = $operation->getTargetPackage();
      }
      elseif ($operation instanceof UninstallOperation) {
        $package = $operation->getPackage();
      }

      if ($package && $package instanceof PackageInterface) {
        /** @var \Composer\Installer\InstallationManager $installationManager */
        $installationManager = $this->composer->getInstallationManager();

        $path = $installationManager->getInstallPath($package);
        $this->io->write(sprintf('Event called: %s, Package: %s, Path: %s', $event->getName(), $package->getName(), $path), TRUE);
      }

    }
    else {
      $this->io->write(sprintf('Event called: %s, <error>no package event</error>', $event->getName()), TRUE);
    }

  }

}
