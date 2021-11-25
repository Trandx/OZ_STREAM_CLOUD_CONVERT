ZfGetid3Module
=======
[![Build Status](https://travis-ci.org/guliano/zf-getid3-module.svg)](https://travis-ci.org/guliano/zf-getid3-module)
[![Coverage Status](https://coveralls.io/repos/guliano/zf-getid3-module/badge.svg?branch=master)](https://coveralls.io/r/guliano/zf-getid3-module?branch=master)

Introduction
------------

This small module wraps GetID3 library written by James Heinrich (http://www.getid3.org/) for Zend Framework 2. GetID3 instance can be accessed via `ServiceLocator`.

Requirements
------------

Please see the [composer.json](composer.json) file.

Installation
------------

Run the following `composer` command:

```console
$ composer require guliano/zf-getid3-module
```

Alternately, manually add the following to your `composer.json`, in the `require` section:

Finally, add the module name to your project's `config/application.config.php` under the `modules`
key:

```php
return array(
    /* ... */
    'modules' => array(
        /* ... */
        'ZfGetid3Module',
    ),
    /* ... */
);
```

Usage
=====
To use this library, just get the GetID3 instance by `ServiceLocator` like this:
```php
/* @var $getid3 \getID3 */
$getid3 = $serviceLocator->get('MediaParser');
$result = $getid3->analyze($pathToFile);
/* do something... */
```