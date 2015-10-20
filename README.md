# Composer preserve paths

Composer plugin for preserving paths while installing, updating or uninstalling packages.

This way you can:

* provide custom files or directories that will not be overwritten on `composer install` or `composer update`
* place packages within the directory of another package (using a composer installer like
[composer/installers](https://packagist.org/packages/composer/installers) or
[davidbarratt/custom-installer](https://packagist.org/packages/davidbarratt/custom-installer))


## Installation

Simply install the plugin with composer: `composer require derhasi/composer-preserve-paths`

## Configuration

For configuring the paths you need to set `preserve-paths` within the `extra` of your root `composer.json`.

```json
{
    "extra": {
        "preserve-paths": [
          "htdocs/sites/all/modules/contrib",
          "htdocs/sites/all/themes/contrib",
          "htdocs/sites/all/libraries",
          "htdocs/sites/all/drush"
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
    "derhasi/composer-preserve-paths": "0.1.*",
    "drupal/views": "7.*",
    "drupal/drupal": "7.*"
  },
  "config": {
    "vendor-dir": "vendor"
  },
  "extra": {
    "custom-installer": {
      "drupal-module": "htdocs/sites/all/modules/contrib/{$name}/",
      "drupal-theme": "htdocs/sites/all/themes/contrib/{$name}/",
      "drupal-library": "htdocs/sites/all/libraries/{$name}/",
      "drupal-drush": "htdocs/sites/all/drush/{$name}/",
      "drupal-profile": "htdocs/profiles/{$name}/",
      "drupal-core": "htdocs/"
    },
    "preserve-paths": [
      "htdocs/sites/all/modules/contrib",
      "htdocs/sites/all/themes/contrib",
      "htdocs/sites/all/libraries",
      "htdocs/sites/all/drush"
    ]
  }
}
```


