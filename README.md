Drupal Commerce
===============

Drupal Commercce is an open source eCommerce framework that is designed to
enable flexible eCommerce for websites and applications based on Drupal.

NOTE!! Unstable Dev Version
---------------------------

This is a non-functional dev version. You're welcome to interact with us in the
issue queue and submit patches / pull requests. 

[Issue Tracker](https://drupal.org/project/issues/commerce)

Getting Started
---------------

We have a great community website where you can watch how-to videos, ask
questions and read documentation.

We strive to be developer friendly. We've included a lot of comments in the code
itself to help developers understand the core APIs and inner-workings of the
Commerce systems. We're continuing to flesh out the Developer Guide on
DrupalCommerce.org, and we encourage developers to file bug reports in the issue
tracker and find support in IRC (#drupal-commerce on irc.freenode.net) where we
try to be very responsive.

[Learn more](https://www.drupalcommerce.org) | 
[Developer Guide](http://www.drupalcommerce.org/developer-guide) | 
[Issue Tracker](http://drupal.org/project/issues/commerce)

How to Install
--------------

1. Download the Commerce module (and dependencies) into /sites/all/modules
   - Tree              https://www.drupal.org/project/tree
   - Addressfield      https://www.drupal.org/project/addressfield
   - Rules             https://www.drupal.org/project/rules
   - Composer Manager  https://www.drupal.org/project/composer_manager
   - Search API        https://www.drupal.org/project/search_api
2. Go to the Module page at Administer > Modules /admin/modules and enable them.
3. ...
4. Profit!

Included Libraries
------------------

For the 2.x branch of Drupal Commerce, Commerce Guys has moved some of the logic
out of the Drupal world and into the greater PHP community. This effort has
produced three unique eCommerce-related PHP libraries (please star on github):

###[Address](https://github.com/commerceguys/address)

This component stores and manipulates postal addresses, more precisely the kind of 
postal addresses meant to identify a precise recipient location for shipping purposes.

###[Zone](https://github.com/commerceguys/zone)

Zone checks if an address matches a set of conditions.

###[Pricing](https://github.com/commerceguys/pricing)

A component for managing prices, taxes, discounts, fees.

Maintainers
-----------

Maintained by Ryan Szrama and Bojan Zivanovic of Commerce Guys.
