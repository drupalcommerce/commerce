#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Ensure the right Drupal version is installed.
# Note: This function is re-entrant.
drupal_ti_ensure_drupal

# Add custom modules to drupal build.
cd "$DRUPAL_TI_DRUPAL_DIR"

# Download custom branches of address and composer_manager.
(
    # These variables come from environments/drupal-*.sh
    mkdir -p "$DRUPAL_TI_MODULES_PATH"
    cd "$DRUPAL_TI_MODULES_PATH"

    git clone --branch 8.x-1.x http://git.drupal.org/project/composer_manager.git
    git clone --branch 8.x-1.x http://git.drupal.org/project/address.git
    git clone --branch 8.x-1.x http://git.drupal.org/project/entity.git
    git clone --branch 8.x-1.x http://git.drupal.org/project/inline_entity_form.git
    git clone --branch 8.x-1.x http://git.drupal.org/project/state_machine.git
    git clone --branch 8.x-1.x http://git.drupal.org/project/profile.git
)

# Ensure the module is linked into the codebase.
drupal_ti_ensure_module_linked

# Initialize composer_manager.
php modules/composer_manager/scripts/init.php
composer drupal-rebuild
composer update -n --lock --verbose

# Enable main module and submodules.
drush en -y commerce commerce_product commerce_order commerce_checkout

# Turn on PhantomJS for functional Javascript tests
phantomjs --ssl-protocol=any --ignore-ssl-errors=true $DRUPAL_TI_DRUPAL_DIR/vendor/jcalderonzumba/gastonjs/src/Client/main.js 8510 1024 768 2>&1 >> /dev/null &
