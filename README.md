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
3. Enable Commerce (but not any of the submodules!)
4. Run `drush composer-manager install`.
   This will download the required libraries into your sites/all/vendor directory.
4. Enable the Commerce submodules. Use the admin/modules page, Drush won't work currently**.

Notes:
- * Devel is currently not optional because of a core bug: https://www.drupal.org/node/2315801
- ** Drush is currently incompatible with composer_manager, causing the library classes to not be found in all commands (enabling a module, clearing cache, etc). The bug is tracked at https://www.drupal.org/node/2208949

Related Libraries
------------------

For the 2.x branch of Drupal Commerce, Commerce Guys has moved some of the logic
out of the Drupal world and into the greater PHP community. This effort has
produced four unique eCommerce-related PHP libraries

###[Intl](https://github.com/commerceguys/intl)

An internationalization library powered by CLDR data.
Handles currencies, currency formatting, and more.

###[Addressing](https://github.com/commerceguys/addressing)

An addressing library, powered by Google's dataset.
Stores and manipulates postal addresses, meant to identify a precise recipient location for shipping or billing purposes.

###[Pricing](https://github.com/commerceguys/pricing)

A component for managing prices, taxes, discounts, fees.

###[Zone](https://github.com/commerceguys/zone)

Zone checks if an address matches a set of conditions.

Maintainers
-----------

Maintained by [Ryan Szrama](https://www.drupal.org/u/rszrama) and
[Bojan Zivanovic](https://www.drupal.org/u/bojanz) of
[Commerce Guys](http://commerceguys.com/).
