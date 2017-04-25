<?php

namespace Drupal\commerce_price\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\commerce_price\Plugin\Field\FieldType\PriceItem;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates the currency constraint.
 */
class CurrencyConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CurrencyConstraintValidator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
    if (!($value instanceof PriceItem)) {
      throw new UnexpectedTypeException($value, PriceItem::class);
    }

    $price_item = $value;
    $currency_code = $price_item->get('currency_code')->getValue();
    if ($currency_code === NULL || $currency_code === '') {
      return;
    }

    $currencies = $this->entityTypeManager->getStorage('commerce_currency')->loadMultiple();
    if (!isset($currencies[$currency_code])) {
      $this->context->buildViolation($constraint->invalidMessage)
        ->atPath('currency_code')
        ->setParameter('%value', $this->formatValue($currency_code))
        ->addViolation();
      return;
    }

    $available_currencies = $constraint->availableCurrencies;
    if (!empty($available_currencies) && !in_array($currency_code, $available_currencies)) {
      $this->context->buildViolation($constraint->notAvailableMessage)
        ->atPath('currency_code')
        ->setParameter('%value', $this->formatValue($currency_code))
        ->addViolation();
    }
  }

}
