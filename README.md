# Composer preserve paths

[![Build Status](https://travis-ci.org/deminy/composer-preserve-paths.svg?branch=master)](https://travis-ci.org/deminy/composer-preserve-paths)
[![HHVM Status](http://hhvm.h4cc.de/badge/deminy/composer-preserve-paths.svg)](http://hhvm.h4cc.de/package/deminy/composer-preserve-paths)
[![Latest Stable Version](https://poser.pugx.org/deminy/composer-preserve-paths/v/stable.svg)](https://packagist.org/packages/deminy/composer-preserve-paths)
[![Latest Unstable Version](https://poser.pugx.org/deminy/composer-preserve-paths/v/unstable.svg)](https://packagist.org/packages/deminy/composer-preserve-paths)
[![License](https://poser.pugx.org/deminy/composer-preserve-paths/license.svg)](https://packagist.org/packages/deminy/composer-preserve-paths)

Composer plugin for preserving paths while installing, updating or uninstalling packages.

This way you can:

* provide custom files or directories that will not be overwritten on `composer install` or `composer update`
* place packages within the directory of another package (using a composer installer like
[composer/installers](https://packagist.org/packages/composer/installers) or
[davidbarratt/custom-installer](https://packagist.org/packages/davidbarratt/custom-installer))

This plugin was originally developed by [Johannes Haseitl](https://github.com/derhasi/composer-preserve-paths). I updated it to allow wildcard pattern matching when defining preserved paths, which is necessary if you have many sites installed with same Drupal installation (i.e., you have many directories like _example.com_, _example.net_, _example.org_, etc under folder _sites/_ of your Drupal installation).

## Installation

Simply install the plugin with composer: `composer require deminy/composer-preserve-paths`

## Configuration

For configuring the paths you need to set `preserve-paths` within the `extra` of your root `composer.json`.

```json
{
    "extra": {
        "preserve-paths": [
          "htdocs/sites/all/modules/contrib",
          "htdocs/sites/all/themes/contrib",
          "htdocs/sites/all/libraries",
          "htdocs/sites/all/drush",
          "htdocs/sites/*.com",
          "htdocs/sites/*.net",
          "htdocs/sites/*.org"
        ]
      }
}
```

## Example

An example composer.json using [davidbarratt/custom-installer](https://packagist.org/packages/davidbarratt/custom-installer):

```json
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://packagist.drupal-composer.org/"
    }
  ],
  "require": {
    "davidbarratt/custom-installer": "dev-master",
    "deminy/composer-preserve-paths": "dev-master",
    "drupal/views": "7.*",
    "drupal/drupal": "7.*"
  },
  "config": {
    "vendor-dir": "vendor"
  },
  "extra": {
    "custom-installer": {
      "drupal-module":  "htdocs/sites/all/modules/contrib/{$name}/",
      "drupal-theme":   "htdocs/sites/all/themes/contrib/{$name}/",
      "drupal-library": "htdocs/sites/all/libraries/{$name}/",
      "drupal-drush":   "htdocs/sites/all/drush/{$name}/",
      "drupal-profile": "htdocs/profiles/{$name}/",
      "drupal-core":    "htdocs/"
    },
    "preserve-paths": [
      "htdocs/sites/all/modules/contrib",
      "htdocs/sites/all/themes/contrib",
      "htdocs/sites/all/libraries",
      "htdocs/sites/all/drush",
      "htdocs/sites/*.com",
      "htdocs/sites/*.net",
      "htdocs/sites/*.org"
    ]
  }
}
```
