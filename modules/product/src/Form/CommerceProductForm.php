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
    /* @var \Drupal\commerce_product\Entity\CommerceProduct $product */
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
    $product = $this->entity;
    $current_user = $this->currentUser();

    $form['advanced'] = array(
      '#type' => 'vertical_tabs',
      '#attributes' => array('class' => array('entity-meta')),
      '#weight' => 99,
    );
    $form = parent::form($form, $form_state);

    // Add a log field if the "Create new revision" option is checked, or if the
    // current user has the ability to check that option.
    $form['revision_information'] = array(
      '#type' => 'details',
      '#group' => 'advanced',
      '#title' => t('Revision information'),
      // Open by default when "Create new revision" is checked.
      '#open' => $product->isNewRevision(),
      '#attributes' => array(
        'class' => array('product-form-revision-information'),
      ),
      '#attached' => array(
        'library' => array('commerce_product/drupal.commerce_product'),
      ),
      '#weight' => 20,
      '#optional' => TRUE,
      '#access' => $product->isNewRevision() || $current_user->hasPermission('administer products'),
    );

    $form['revision'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Create new revision'),
      '#default_value' => $product->isNewRevision(),
      '#access' => $current_user->hasPermission('administer products'),
      '#group' => 'revision_information',
    );

    $form['revision_log'] += array(
      '#states' => array(
        'visible' => array(
          ':input[name="revision"]' => array('checked' => TRUE),
        ),
      ),
      '#group' => 'revision_information',
    );

    // Product author information for administrators.
    $form['author'] = array(
      '#type' => 'details',
      '#title' => t('Authoring information'),
      '#group' => 'advanced',
      '#attributes' => array(
        'class' => array('product-form-author'),
      ),
      '#attached' => array(
        'library' => array('commerce_product/drupal.commerce_product'),
      ),
      '#weight' => 90,
      '#optional' => TRUE,
    );

    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'author';
    }

    if (isset($form['created'])) {
      $form['created']['#group'] = 'author';
    }

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
