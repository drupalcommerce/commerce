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
	git clone --branch 8.x-1.x http://git.drupal.org/project/inline_entity_form.git
)

# Enable and run composer_manager.
drush pm-enable composer_manager --yes
drush composer-manager-init

# Ensure the module is linked into the codebase.
drupal_ti_ensure_module_linked

# Rebuild core dependencies.
# @todo Is that really needed?
cd core
rm -rf vendor
composer drupal-rebuild
composer update --prefer-source -n --verbose
cd ..

# Enable main module and submodules.
drush en -y commerce commerce_product commerce_order
