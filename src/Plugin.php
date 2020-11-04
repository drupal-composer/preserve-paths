<?php

/**
 * @file
 * Contains DrupalComposer\PreservePaths\Plugin.
 */

namespace DrupalComposer\PreservePaths;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;

/**
 * Class Plugin.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{

    /**
     * @var \DrupalComposer\PreservePaths\PluginWrapper
     */
    protected $wrapper;

    /**
     * {@inheritdoc}
     */
    public function activate(Composer $composer, IOInterface $io)
    {
        $this->wrapper = new PluginWrapper($composer, $io);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
      return [
        PackageEvent::PRE_PACKAGE_INSTALL => 'prePackage',
        PackageEvent::POST_PACKAGE_INSTALL => 'postPackage',
        PackageEvent::PRE_PACKAGE_UPDATE => 'prePackage',
        PackageEvent::POST_PACKAGE_UPDATE => 'postPackage',
        PackageEvent::PRE_PACKAGE_UNINSTALL => 'prePackage',
        PackageEvent::POST_PACKAGE_UNINSTALL => 'postPackage',
      ];
    }

    /**
     * Pre Package event behaviour for backing up preserved paths.
     *
     * @param \Composer\Installer\PackageEvent $event
     */
    public function prePackage(PackageEvent $event)
    {
        $this->wrapper->prePackage($event);
    }

    /**
     * Pre Package event behaviour for backing up preserved paths.
     *
     * @param \Composer\Installer\PackageEvent $event
     */
    public function postPackage(PackageEvent $event)
    {
        $this->wrapper->postPackage($event);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
