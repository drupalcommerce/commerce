<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\CommerceProductForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the product edit form.
 */
class CommerceProductForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
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
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_product\Entity\CommerceProduct $product */
    $form = parent::form($form, $form_state);
    $product = $this->entity;
    $account = $this->currentUser();

    if ($product->isNew()) {
      $form['status'];
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
      '#access' => $product->isNewRevision() || $account->hasPermission('administer products'),
    );

    $form['revision_information']['revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $product->isNewRevision(),
      '#access' => $account->hasPermission('administer products'),
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    /** @var \Drupal\commerce_product\entity\CommerceProduct $product */
    $product = $this->getEntity();

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('revision')) {
      $product->setNewRevision();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\entity\CommerceProduct $product */
    $product = $this->getEntity();
    try {
      $product->save();
      drupal_set_message($this->t('The product %product_label has been successfully saved.', array('%product_label' => $product->label())));
      $form_state->setRedirect('entity.commerce_product.view', array('commerce_product' => $product->id()));
    }
    catch (\Exception $e) {
      drupal_set_message($this->t('The product %product_label could not be saved.', array('%product_label' => $product->label())), 'error');
      $this->logger('commerce_product')->error($e);
      $form_state->setRebuild();
    }
  }

}
