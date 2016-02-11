<?php
namespace Drupal\commerce_tax\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\commerce_tax\Entity\TaxType;

class TaxTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_tax\Entity\TaxTypeInterface $tax_type */
    $tax_type = $this->entity;
    /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $zone_storage */
    $zone_storage = $this->entityTypeManager->getStorage('zone');
    $zones = $zone_storage->loadMultipleOverrideFree();
    // @todo Filter by zone scope == 'tax'.
    $zones = array_map(function ($zone) {
      return $zone->label();
    }, $zones);

    $form['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $tax_type->getName(),
      '#maxlength' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $tax_type->getId(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_tax\Entity\TaxType::load',
        'source' => ['name'],
      ],
      '#required' => TRUE,
      '#disabled' => !$tax_type->isNew(),
    ];
    $form['zone'] = [
      '#type' => 'select',
      '#title' => $this->t('Zone'),
      '#default_value' => $tax_type->getZoneId(),
      '#options' => $zones,
      '#required' => TRUE,
    ];
    if ($tax_type->isNew()) {
      $link = Link::createFromRoute('Zones page', 'entity.zone.collection')->toString();
      $form['zone']['#description'] = $this->t('To add a new zone visit the @link.', ['@link' => $link]);
    }
    $form['compound'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Compound'),
      '#description' => $this->t("Compound tax is calculated on top of a primary tax. For example, Canada's Provincial Sales Tax (PST) is compound, calculated on a price that already includes the Goods and Services Tax (GST)."),
      '#default_value' => $tax_type->isCompound(),
    ];
    $form['displayInclusive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display inclusive'),
      '#default_value' => $tax_type->isDisplayInclusive(),
    ];
    $form['roundingMode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Rounding mode'),
      '#default_value' => $tax_type->getRoundingMode() ?: TaxType::ROUND_HALF_UP,
      '#options' => [
        TaxType::ROUND_HALF_UP => $this->t('Round up'),
        TaxType::ROUND_HALF_DOWN => $this->t('Round down'),
        TaxType::ROUND_HALF_EVEN => $this->t('Round even'),
        TaxType::ROUND_HALF_ODD => $this->t('Round odd'),
      ],
      '#required' => TRUE,
    ];
    $form['tag'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tag'),
      '#description' => $this->t('Used by the resolvers to analyze only the tax types relevant to them. For example, the EuTaxTypeResolver would analyze only the tax types with the "EU" tag.'),
      '#default_value' => $tax_type->getTag(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    drupal_set_message($this->t('Saved the %label tax type.', [
      '%label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('entity.commerce_tax_type.collection');
  }

}
