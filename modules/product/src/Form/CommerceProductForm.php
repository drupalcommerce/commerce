<?php

/**
 * @file
 * Contains Drupal\commerce\CommerceProductForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the product edit form.
 */
class CommerceProductForm extends ContentEntityForm {

  /**
   * Overrides \Drupal\Core\Entity\EntityForm::prepareEntity().
   *
   * Prepares the product object.
   *
   * Fills in a few default values, and then invokes hook_commerce_product_prepare()
   * on all modules.
   */
  protected function prepareEntity() {
    $product = $this->entity;
    // Set up default values, if required.
    $product_type = entity_load('commerce_product_type', $product->bundle());
    if (!$product->isNew()) {
      $product->setRevisionLog(NULL);
    }
    // Always use the default revision setting.
    $product->setNewRevision($product_type->revision);
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\commerce_product\Entity\CommerceProduct */
    $form = parent::form($form, $form_state);
    $product = $this->entity;
    $account = $this->currentUser();

    if ($product->isNew()) {
      $form['status']['widget'];
    }

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 99,
    );

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'details',
      '#title' => $this->t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $product->isNewRevision(),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('product-form-revision-information'),
      ),
      '#attached' => array(
        'library' => array('commerce_product/drupal.commerce_product'),
      ),
      '#weight' => 20,
      '#access' => $product->isNewRevision() || $account->hasPermission('administer commerce_product entities'),
    );

    $form['revision_information']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $product->isNewRevision(),
      '#access' => $account->hasPermission('administer commerce_product entities'),
    );

    // Check the revision log checkbox when the log textarea is filled in.
    // This must not happen if "Create new revision" is enabled by default,
    // since the state would auto-disable the checkbox otherwise.
    if (!$product->isNewRevision()) {
      $form['revision_information']['revision']['#states'] = array(
        'checked' => array(
          'textarea[name="revision_log"]' => array('empty' => FALSE),
        ),
      );
    }

    $form['revision_information']['revision_log'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Revision log message'),
      '#rows' => 4,
      '#default_value' => $product->getRevisionLog(),
      '#description' => $this->t('Briefly describe the changes you have made.'),
    );

    return $form;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityFormController::submit().
   */
  public function submit(array $form, FormStateInterface $form_state) {
    // Build the entity object from the submitted values.
    $entity = parent::submit($form, $form_state);

    $form_state->setRedirect('entity.commerce_product.list');

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision')) {
      $entity->setNewRevision();
    }

    return $entity;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    try {
      $this->entity->save();
      drupal_set_message($this->t('The product %product_label has been successfully saved.', array('%product_label' => $this->entity->label())));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The product %product_label could not be saved.', array('%product_label' => $this->entity->label())), 'error');
      $this->logger('commerce_product')->error($e);
    }
  }

}
