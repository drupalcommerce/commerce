<?php

namespace Drupal\commerce_tax\Controller;

use Drupal\commerce\UrlData;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\SupportsVerificationInterface;
use Drupal\commerce_tax\Plugin\Commerce\TaxNumberType\VerificationResult;
use Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class TaxNumberController implements ContainerInjectionInterface {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new TaxNumberController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * Verifies the given tax number.
   *
   * @param string $tax_number
   *   The tax number.
   * @param string $context
   *   The encoded context.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the context is invalid or the user doesn't have access to update
   *   the parent entity.
   */
  public function verify($tax_number, $context) {
    $context = $this->prepareContext($context);
    if (!$context) {
      throw new AccessDeniedHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $context['entity'];
    if (!$entity->access('update')) {
      throw new AccessDeniedHttpException();
    }
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $field */
    $field = $entity->get('tax_number')->first();
    $type_plugin = $field->getTypePlugin();
    if ($type_plugin instanceof SupportsVerificationInterface) {
      $result = $type_plugin->verify($tax_number);
      $field->applyVerificationResult($result);
      $entity->save();
      $this->messenger()->addStatus($this->t('The tax number @number has been reverified.', [
        '@number' => $tax_number,
      ]));
    }

    return new RedirectResponse(Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString());
  }

  /**
   * Displays the verification result for the given tax number.
   *
   * @param string $tax_number
   *   The tax number.
   * @param string $context
   *   The encoded context.
   *
   * @return array
   *   A renderable array.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the context is invalid or the user doesn't have access to update
   *   the parent entity.
   */
  public function result($tax_number, $context) {
    $context = $this->prepareContext($context);
    if (!$context) {
      throw new AccessDeniedHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $context['entity'];
    if (!$entity->access('update')) {
      throw new AccessDeniedHttpException();
    }
    /** @var \Drupal\commerce_tax\Plugin\Field\FieldType\TaxNumberItemInterface $field */
    $field = $entity->get('tax_number')->first();
    if ($field->value != $tax_number) {
      throw new AccessDeniedHttpException();
    }

    $result = [];
    $type_plugin = $field->getTypePlugin();
    if ($type_plugin instanceof SupportsVerificationInterface) {
      $verification_result = new VerificationResult(
        $field->verification_state,
        $field->verification_timestamp,
        $field->verification_result
      );
      $result = $type_plugin->renderVerificationResult($verification_result);
      // @todo Move this to a Twig template, to allow it to be customized.
      if ($field->verification_timestamp) {
        $result['timestamp'] = [
          '#type' => 'item',
          '#title' => $this->t('Timestamp'),
          '#plain_text' => $this->dateFormatter->format($field->verification_timestamp, 'long'),
          '#weight' => -10,
        ];
      }
    }

    return $result;
  }

  /**
   * Parses and validates the context.
   *
   * @param string $context
   *   The context string.
   *
   * @return array|false
   *   The prepared context, or FALSE if validation failed.
   */
  protected function prepareContext($context) {
    $context = UrlData::decode($context);
    $context = $context ?: [];
    if (!count($context) == 4) {
      return FALSE;
    }
    // Assign keys. The context array is numerically indexed to save space.
    $keys = ['entity_type', 'entity_id', 'field_name', 'view_mode'];
    $context = array_combine($keys, $context);
    foreach ($keys as $key) {
      if (empty($context[$key])) {
        // Missing required data.
        return FALSE;
      }
    }
    // Validate the provided values.
    try {
      $storage = $this->entityTypeManager->getStorage($context['entity_type']);
    }
    catch (PluginNotFoundException $e) {
      return FALSE;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $storage->load($context['entity_id']);
    if (!$entity || !$entity->hasField($context['field_name'])) {
      return FALSE;
    }
    $field = $entity->get($context['field_name'])->first();
    if (!($field instanceof TaxNumberItemInterface)) {
      return FALSE;
    }
    // Upcast the entity in the context array.
    $context['entity'] = $entity;

    return $context;
  }

}
