<?php

/**
 * @file
 * Contains deminy\Composer\Plugin.
 */

namespace deminy\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;

/**
 * Class Plugin.
 */
class Plugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var PluginWrapper
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
     * @inheritdoc
     */
    public static function getSubscribedEvents() 
    {
        return array(
            PackageEvents::PRE_PACKAGE_INSTALL    => 'prePackage',
            PackageEvents::POST_PACKAGE_INSTALL   => 'postPackage',
            PackageEvents::PRE_PACKAGE_UPDATE     => 'prePackage',
            PackageEvents::POST_PACKAGE_UPDATE    => 'postPackage',
            PackageEvents::PRE_PACKAGE_UNINSTALL  => 'prePackage',
            PackageEvents::POST_PACKAGE_UNINSTALL => 'postPackage',
        );
    }

    /**
     * Pre Package event behaviour for backing up preserved paths.
     *
     * @param PackageEvent $event
     */
    public function prePackage(PackageEvent $event) 
    {

        $this->wrapper->prePackage($event);
    }

    /**
     * Pre Package event behaviour for backing up preserved paths.
     *
     * @param PackageEvent $event
     */
    public function postPackage(PackageEvent $event) 
    {
        $this->wrapper->postPackage($event);
    }
}
