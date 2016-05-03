<?php

/**
 * @file
 * Hooks provided by commerce_product module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Allows modules to add arbitrary AJAX commands to the Ajax response returned
 * from the Add to Cart form attributes refresh.
 *
 * When a product selection widget's value is changed, whether it is a product
 * select list or a product attribute field widget, the Add to Cart form gets
 * an AJAX refresh. The form will be rebuilt using the new form state and the
 * AJAX callback of the element that was changed will be called.
 *
 * This particular AJAX refresh function returns an AjaxResponse object
 * that perform HTML replacement on the page. However, other modules
 * may want to interact with the refreshed form. They can use this hook to
 * add additional items to the AjaxResponse object. Note that the form array
 * cannot be altered, just the commands.
 *
 * @param Drupal\Core\Ajax\AjaxResponse $response
 *   The response object to refresh the cart form with updated form
 *   elements and to replace product fields rendered on the page to match the
 *   currently selected product.
 * @param array $form
 *   The rebuilt form array.
 * @param Drupal\Core\Form\FormStateInterface $form_state
 *   The form state array from the form.
 *
 * @see \Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationAttributesWidget::ajaxRefresh()
 */
function hook_commerce_product_attributes_refresh_alter(\Drupal\Core\Ajax\AjaxResponse $response, array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
  // Display an alert message.
  $response->addCommand(new\Drupal\Core\Ajax\AlertCommand(t('Changed product variation!')));
}
