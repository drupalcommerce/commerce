Drupal Commerce
===============
[![Build Status](https://travis-ci.org/commerceguys/commerce.svg?branch=8.x-2.x)](https://travis-ci.org/commerceguys/commerce)

Drupal Commerce is an open source eCommerce framework that is designed to
enable flexible eCommerce for websites and applications based on Drupal.

NOTE!! Unstable Dev Version
---------------------------

This is a non-functional dev version. You're welcome to interact with us in the
issue queue and submit patches / pull requests.

[Issue Tracker](https://drupal.org/project/issues/commerce)

Installation
------------------

1. Install the latest -dev version of Drupal 8.
2. Install the latest -dev versions of [devel*](https://www.drupal.org/project/devel) and [composer_manager](https://drupal.org/project/composer_manager).
3. Download Commerce
4. Init composer manager.
```Shell
drush composer-manager-init
```
5. Go to core/ directory and update drupal components with composer.
```Shell
cd core/
composer drupal-update
```
6. Enable the Commerce modules and submodules.

Notes:
- * Devel is currently not optional because of a core bug: https://www.drupal.org/node/2315801

Related Libraries
------------------

For the 2.x branch of Drupal Commerce, Commerce Guys has moved some of the logic
out of the Drupal world and into the greater PHP community.

###[Intl](https://github.com/commerceguys/intl)

An internationalization library powered by CLDR data.
Handles currencies, currency formatting, and more.

###[Addressing](https://github.com/commerceguys/addressing)

An addressing library, powered by Google's dataset.
Stores and manipulates postal addresses, meant to identify a precise recipient location for shipping or billing purposes.

###[Zone](https://github.com/commerceguys/zone)

A zone library. Zones are territorial groupings mostly used for shipping or tax purposes.

###[Tax](https://github.com/commerceguys/tax)

A tax library with a flexible data model, predefined tax rates, powerful resolving logic.

###[Pricing](https://github.com/commerceguys/pricing)

A component for managing prices, taxes, discounts, fees.

Maintainers
-----------

Maintained by [Ryan Szrama](https://www.drupal.org/u/rszrama) and
[Bojan Zivanovic](https://www.drupal.org/u/bojanz) of
[Commerce Guys](http://commerceguys.com/).
