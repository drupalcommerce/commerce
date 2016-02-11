<?php
namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TaxRateAmountForm extends EntityForm {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new TaxRateAmountForm object.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.query'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_tax\Entity\TaxRateAmountInterface $tax_rate_amount */
    $tax_rate_amount = $this->entity;

    $form['rate'] = [
      '#type' => 'hidden',
      '#value' => $tax_rate_amount->getRateId(),
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $tax_rate_amount->getId(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#field_prefix' => $tax_rate_amount->getRateId() . '_',
      '#required' => TRUE,
      '#disabled' => !$tax_rate_amount->isNew(),
    ];
    $form['amount'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount'),
      '#default_value' => $tax_rate_amount->getAmount(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['startDate'] = [
      '#type' => 'date',
      '#title' => $this->t('Start date'),
      '#default_value' => $tax_rate_amount->getStartDate(),
    ];
    $form['endDate'] = [
      '#type' => 'date',
      '#title' => $this->t('End date'),
      '#default_value' => $tax_rate_amount->getEndDate(),
    ];

    return $form;
  }

  /**
   * Determines if the tax rate amount already exists.
   *
   * @param string $id
   *   The tax rate amount ID.
   * @param array $element
   *   The form element.
   *
   * @return bool
   *   TRUE if the tax rate amount exists, FALSE otherwise.
   */
  public function exists($id, array $element) {
    return (bool) $this->queryFactory
      ->get('commerce_tax_rate_amount')
      ->condition('id', $element['#field_prefix'] . $id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $form_state->setValueForElement($form['id'], $form['id']['#field_prefix'] . $form_state->getValue('id'));
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label tax rate.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirectUrl($this->entity->toUrl('canonical'));
  }

}
