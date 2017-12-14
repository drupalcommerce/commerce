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
use Drupal\Core\Entity\EntityTypeManagerInterface;


/**
 * Checks if the Vat number is valid.
 */
class TaxNumberConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CountryConstraintValidator object.
   *
   * @param \CommerceGuys\Addressing\Country\CountryRepositoryInterface $country_repository
   *   The country repository.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
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
      $address = $profile->hasField('address') ? $profile->get('address')->first() : FALSE;

      $address_country_code = $address instanceof AddressItem ? $address->getCountryCode() : NULL;

      // Check if the tax number is valid for the chosen country.
      // @todo: We need to limit the plugins the number is checked against
      // to tax types that have the address country code in their zones.

      $tax_number_is_valid = FALSE;
      foreach ($this->entityTypeManager->getStorage('commerce_tax_type')->loadMultiple() as $tax_type) {
        $tax_type_plugin = $tax_type->getPlugin();

        if ($tax_type_plugin->isValidTaxNumber($tax_number, $address_country_code)) {
          $tax_number_is_valid = TRUE;
        }
      }

      // If number couldn't be validated for any active tax country.
      if (!$tax_number_is_valid) {
        $this->context->addViolation($constraint->notValid);
      }
    }
  }

}
