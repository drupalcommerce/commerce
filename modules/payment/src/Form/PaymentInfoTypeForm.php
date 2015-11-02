<?php

/**
 * @file
 * Contains \Drupal\commerce_payment\Form\PaymentInfoTypeForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\BundleEntityFormBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaymentInfoTypeForm extends BundleEntityFormBase {

  /**
   * The payment info type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $paymentInfoTypeStorage;

  /**
   * Create an IndexForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $paymentInfoTypeStorage
   *   The payment info type storage.
   */
  public function __construct(EntityStorageInterface $paymentInfoTypeStorage) {
    // Setup object members.
    $this->paymentInfoTypeStorage = $paymentInfoTypeStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityManagerInterface $entityManager */
   $entityManager = $container->get('entity.manager');
   return new static($entityManager->getStorage('commerce_payment_info_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $paymentInformationType = $this->entity;

    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $paymentInformationType->label(),
      '#description' => $this->t('Label for the payment information type.'),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $paymentInformationType->id(),
      '#machine_name' => array(
        'exists' => array($this->paymentInfoTypeStorage, 'load'),
        'source' => array('label'),
      ),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
    );

    $form['description'] = array(
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $paymentInformationType->getDescription(),
      '#description' => $this->t('Description of this payment information type'),
    );

    return $this->protectBundleIdElement($form);;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $paymentInformationType = $this->entity;
    $paymentInformationType->save();
    drupal_set_message($this->t('Saved the %payment_info_type_label payment information type.', array(
      '%payment_info_type_label' => $paymentInformationType->label(),
    )));
    $form_state->setRedirect('entity.commerce_payment_info_type.collection');
  }

}
