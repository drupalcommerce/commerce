<?php

namespace Drupal\commerce_promotion;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the list builder for shipping methods.
 */
class PromotionListBuilder extends EntityListBuilder implements FormInterface {

  /**
   * The key to use for the form element containing the entities.
   *
   * @var string
   */
  protected $entitiesKey = 'promotions';

  /**
   * The entities being listed.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface[]
   */
  protected $entities = [];

  /**
   * The promotion usage counts.
   *
   * @var array
   */
  protected $usages;

  /**
   * Whether tabledrag is enabled.
   *
   * @var bool
   */
  protected $hasTableDrag = TRUE;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_promotions';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    $entities = $this->storage->loadMultiple($entity_ids);
    // Sort the entities using the entity class's sort() method.
    uasort($entities, [$this->entityType->getClass(), 'sort']);

    $usage = \Drupal::getContainer()->get('commerce_promotion.usage');
    $this->usages = $usage->getUsageMultiple($entities);

    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Promotion');
    $header['offer'] = $this->t('Offer');
    $header['usage'] = $this->t('Usage');
    $header['status'] = $this->t('Status');
    $header['coupons'] = $this->t('Coupons');
    if ($this->hasTableDrag) {
      $header['weight'] = $this->t('Weight');
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_promotion\Entity\PromotionInterface $entity */
    $row['#attributes']['class'][] = 'draggable';
    $row['#weight'] = $entity->getWeight();
    $row['name'] = $entity->toLink($entity->label(), 'edit-form')->toRenderable();

    /** @var \Drupal\commerce\Plugin\Field\FieldType\PluginItem $offer */
    $offer = $entity->get('offer')->first();
    $row['offer'] = $offer->getTargetDefinition()['label'];

    $usage_limit = $entity->getUsageLimit();
    if ($usage_limit == 0) {
      $row['usage'] = '&infin;';
    }
    else {
      $row['usage'] = $this->usages[$entity->id()] . ' / ' . $entity->getUsageLimit();
    }

    if (!$entity->isEnabled()) {
      $status = $this->t('Disabled');
    }
    elseif ($usage_limit > 0 && $this->usages[$entity->id()] >= $usage_limit) {
      $status = $this->t('Inactive');
    }
    else {
      $now = new DrupalDateTime();

      $start_date = $entity->getStartDate();
      $start_now_diff = $start_date->diff($now);

      $end_date = $entity->getEndDate();
      if ($start_now_diff->invert == 1 && $start_now_diff->days >= 0) {
        $status = $this->t('Starts on :date', [
          ':date' => $start_date->format('M, d Y'),
        ]);
      }
      elseif ($end_date) {
        $end_now_diff = $end_date->diff($now);
        if ($end_now_diff->invert == 1 && $end_now_diff->days > 0) {
          $status = $this->t('Ends on :date', [
            ':date' => $end_date->format('M, d Y'),
          ]);
        }
        else {
          $status = $this->t('Inactive');
        }
      }
      else {
        $status = $this->t('Active');
      }
    }

    $row['status'] = $status;

    $coupon_count = $entity->get('coupons')->count();
    $row['coupons'] = $coupon_count ?: $this->t('None');

    if ($this->hasTableDrag) {
      $row['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $entity->label()]),
        '#title_display' => 'invisible',
        '#default_value' => $entity->getWeight(),
        '#attributes' => ['class' => ['weight']],
      ];
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return \Drupal::formBuilder()->getForm($this);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $this->entities = $this->load();
    if (count($this->entities) <= 1) {
      $this->hasTableDrag = FALSE;
    }
    $delta = 10;
    // Dynamically expand the allowed delta based on the number of entities.
    $count = count($this->entities);
    if ($count > 20) {
      $delta = ceil($count / 2);
    }

    $form[$this->entitiesKey] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
    ];
    foreach ($this->entities as $entity) {
      $row = $this->buildRow($entity);
      $row['offer'] = ['#markup' => $row['offer']];
      $row['usage'] = ['#markup' => $row['usage']];
      $row['status'] = ['#markup' => $row['status']];
      $row['coupons'] = ['#markup' => $row['coupons']];
      if (isset($row['weight'])) {
        $row['weight']['#delta'] = $delta;
      }
      $form[$this->entitiesKey][$entity->id()] = $row;
    }

    if ($this->hasTableDrag) {
      $form[$this->entitiesKey]['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'weight',
      ];
      $form['actions']['#type'] = 'actions';
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => t('Save'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue($this->entitiesKey) as $id => $value) {
      if (isset($this->entities[$id]) && $this->entities[$id]->getWeight() != $value['weight']) {
        // Save entity only when its weight was changed.
        $this->entities[$id]->setWeight($value['weight']);
        $this->entities[$id]->save();
      }
    }
  }

}
