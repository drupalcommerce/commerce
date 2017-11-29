<?php

namespace Drupal\commerce_tax\Plugin\Validation\Constraint;


use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\commerce_tax\TaxNumber;
use Drupal\commerce_tax\TaxTypeManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\Profile;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Checks if the Vat number is valid.
 */
class VatNumberConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The tax type manager.
   *
   * @var \Drupal\commerce_tax\TaxTypeManager
   */
  protected $taxTypeManager;

  /**
   * Constructs a new CountryConstraintValidator object.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(TaxTypeManager $tax_type_manager) {
    $this->taxTypeManager = $tax_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.commerce_tax_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    $tax_number = new TaxNumber($value);

    // Check for basic formatting.
    if (!$tax_number->isValidFormat()) {
      $this->context->addViolation($constraint->incorrectFormat);
    }

    // Check if address information is present as the default address field as
    // part of a customer profile entity.
    /* @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $root */
    $root = $this->context->getRoot();

    /* @var \Drupal\profile\Entity\Profile $profile */
    $profile = $root->getValue() instanceof Profile ? $root->getValue() : NULL;

    if ($tax_number->isValidFormat() && $profile) {
      /* @var AddressItem $address */
      $address = $profile->hasField('address') ? $profile->get('address')
        ->first() : FALSE;

      $address_country_code = $address instanceof AddressItem ? $address->getCountryCode() : NULL;

      if ($tax_number->getCountryCode() != $address_country_code) {
        $this->context->addViolation($constraint->incorrectFormat);
      }
    }

    // Check the tax number against tax type validation.
    foreach ($this->taxTypeManager->getDefinitions() as $taxType) {
      // @TODO: Check if structure even halfway right.
      if ($taxType->isPossibleTaxNumber($tax_number)) {
        if (!$taxType->isValidTaxNumber($tax_number)) {
          $this->context->addViolation($constraint->vatNotValid);
        }
      }
    }

  }

}
