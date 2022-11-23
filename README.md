[![Build Status](https://travis-ci.org/ContaoBlackForest/contao-encore-bundle.png)](https://travis-ci.org/ContaoBlackForest/contao-encore-bundle)
[![Latest Version tagged](http://img.shields.io/github/tag/ContaoBlackForest/contao-encore-bundle.svg)](https://github.com/ContaoBlackForest/contao-encore-bundle/tags)
[![Latest Version on Packagist](http://img.shields.io/packagist/v/ContaoBlackForest/contao-encore-bundle.svg)](https://packagist.org/packages/ContaoBlackForest/contao-encore-bundle)
[![Installations via composer per month](http://img.shields.io/packagist/dm/ContaoBlackForest/contao-encore-bundle.svg)](https://packagist.org/packages/ContaoBlackForest/contao-encore-bundle)

Contao Encore Bundle
====================

This Bundle provide the Symfony Webpack Encore for Contao.

It is preconfigured for the webpack encore extension. If you use own things, so overwrite it in your project.

The pre configuration for the extension is:
```yml
webpack_encore:
    output_path: '%kernel.project_dir%/public/layout'

framework:
    assets:
        json_manifest_path: '%kernel.project_dir%/public/layout/manifest.json'
```

How to use Webpack Encore can you read [here](https://github.com/symfony/webpack-encore-bundle) .

It is also possible to load assets individually in a template:
```php
$this->asset('public/layout/tinymce.css');
```

Use in Contao
-------------

In Contao you can add your sources in the page layout.
