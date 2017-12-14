<?php


function commerce_tax_post_update_1() {
  // Ensure that the tax_number field exist on the profile entity.
  // @todo: We can change the hook if we get a better one.
  // https://www.drupal.org/project/drupal/issues/2901418
  if (!\Drupal::entityManager()->getStorage('field_storage_config')->load('profile.tax_number')) {
    // Create storage definition if necessary.
    $config_path = DRUPAL_ROOT . '/' .  drupal_get_path('module', 'commerce_tax') . '/config/install/field.storage.profile.tax_number.yml';
    $file_contents = file_get_contents($config_path);
    $data = Yaml::parse($file_contents);

    \Drupal::configFactory()->getEditable('field.storage.profile.tax_number')->setData($data)->save(TRUE);

    // Create field instance.
    $config_path = DRUPAL_ROOT . '/' . drupal_get_path('module', 'commerce_tax') . '/config/install/field.field.profile.customer.tax_number.yml';
    $file_contents = file_get_contents($config_path);
    $data = Yaml::parse($file_contents);

    \Drupal::configFactory()->getEditable('field.field.profile.customer.tax_number')->setData($data)->save(TRUE);

    $message = 'New configuration imported';
  }

  return $message;
}