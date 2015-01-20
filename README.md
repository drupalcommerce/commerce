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
4. Go to your sites/default/files/composer directory and run `composer install`.

   This will download the required libraries into your sites/all/vendor directory.
4. Enable the Commerce submodules. Use the admin/modules page, Drush won't work currently**.

Notes:
- * Devel is currently not optional because of a core bug: https://www.drupal.org/node/2315801
- ** Drush is currently incompatible with composer_manager, causing the library classes to not be found in all commands (enabling a module, clearing cache, etc). The bug is tracked at https://www.drupal.org/node/2208949

Step by Step Advanced Installation using Drush
------------------
1. Download and install the latest [Drush 7](https://github.com/drush-ops/drush#installupdate---composer)
2. Download the latest -dev version of Drupal 8.

    `git clone --branch 8.0.x http://git.drupal.org/project/drupal.git`

3. Download the latest -dev versions of [devel*](https://www.drupal.org/project/devel) and [composer_manager](https://drupal.org/project/composer_manager).

    `git clone --branch 8.x-1.x http://git.drupal.org/project/devel.git modules/devel`

    `git clone --branch 8.x-1.x http://git.drupal.org/project/composer_manager.git modules/composer_manager`
    
4. Download the latest 8.x-2.x versions of [commerce](https://github.com/commerceguys/commerce) *.

    `git clone --branch 8.x-2.x https://github.com/commerceguys/commerce modules/commerce`

4. Install Drupal 8 *

    `drush si --db-url=mysql://root:@127.0.0.1/drupal --account-name=admin --account-pass=admin --site-mail=admin@example.com --site-name="Commerce" --yes`
5. Enable devel, composer manager, commerce

    `drush pm-enable devel composer_manager simpletest commerce --yes`
6. Add autoload to settings.php
    Mac / Linux: 
    
    `echo "require 'sites/all/vendor/autoload.php';" | sudo tee -a sites/default/settings.php`
    
    Windows: Add `require 'sites/all/vendor/autoload.php';` to sites/default/settings.php
7. Install composer dependencies

    `cd sites/default/files/composer`

    `composer install`

    `cd ../../../../`
8. Enable commerce product and commerce order.

    `drush pm-enable commerce_product commerce_order --yes`

Notes:

- You can run steps 4, 5, 6 and 8, skipping 1, 2, 3, 7 when reinstalling.

- Replace MySQL details with your MySQL details

- Replace the https://github.com/commerceguys/commerce with your fork link.

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
