<?php

/**
 * @file
 * Contains Drupal\commerce_payment\Form\PaymentInfoTypeDeleteForm.
 */

namespace Drupal\commerce_payment\Form;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds the form to delete an payment information type.
 */
class PaymentInfoTypeDeleteForm extends EntityDeleteForm {

  /**
   * Constructs a new LineItemTypeDeleteForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $queryFactory
   *   The entity query object.
   */
  public function __construct(QueryFactory $queryFactory) {
    $this->queryFactory = $queryFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $numPaymentInfo = $this->queryFactory->get('commerce_payment_info')
      ->condition('type', $this->entity->id())
      ->count()
      ->execute();
    if ($numPaymentInfo) {
      $caption = '<p>' . $this->formatPlural($numPaymentInfo, '%type is used by 1 payment information entity on your site. You can not remove this payment information type until you have removed all of the %type payment information entities.', '%type is used by @count payment information entities on your site. You may not remove %type until you have removed all of the %type payment information entities.', array('%type' => $this->entity->label())) . '</p>';
      $form['#title'] = $this->getQuestion();
      $form['description'] = array('#markup' => $caption);
      return $form;
    }
    return parent::buildForm($form, $form_state);
  }

}
