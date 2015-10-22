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
-------------

Preparation:

1. Download the -dev versions of Drupal 8 and [composer_manager](https://drupal.org/project/composer_manager) into your
   `modules` directory.

2. From the Drupal root directory, initialize composer_manager,* and run it for the first time:

   ```sh
   php modules/composer_manager/scripts/init.php
   composer drupal-update
   ```

3. Enable the Commerce modules, e.g.:

   ```sh
   drush en -y commerce commerce_order commerce_product commerce_tax
   ```

Notes:
- * Find out more about composer_manager usage [here](https://www.drupal.org/node/2405811).

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
