#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# @todo Patch drupal_ti to not link, but add self as VCS and require.
if [ -L "$DRUPAL_TI_MODULES_PATH/$DRUPAL_TI_MODULE_NAME" ]
then
  unlink "$DRUPAL_TI_MODULES_PATH/$DRUPAL_TI_MODULE_NAME"
fi

# drupal_ti adds the repos in the wrong order, the module one must be first.
composer config --unset repo.drupal
composer config repositories.commerce path $TRAVIS_BUILD_DIR
composer config repositories.drupal composer https://packages.drupal.org/8
composer config repositories

# Add Commerce. '*@dev' is used because the path repo can't detect any versions
# in PR branches.
cd "$DRUPAL_TI_DRUPAL_DIR"
composer require drupal/commerce *@dev
composer update -n --lock --verbose

# Enable main module and submodules.
drush en -y commerce_product commerce_order commerce_checkout commerce_payment

# Turn on PhantomJS for functional Javascript tests
phantomjs --ssl-protocol=any --ignore-ssl-errors=true $DRUPAL_TI_DRUPAL_DIR/vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1024 768 2>&1 >> /dev/null &
