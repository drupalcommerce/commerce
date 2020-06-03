<?php

namespace Drupal\commerce_promotion\Form;

use Drupal\commerce_promotion\CouponCodePattern;
use Drupal\commerce_promotion\CouponCodeGeneratorInterface;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for bulk generating coupons.
 */
class CouponGenerateForm extends FormBase {

  /**
   * The number of coupons to generate in each batch.
   *
   * @var int
   */
  const BATCH_SIZE = 25;

  /**
   * The maximum code length.
   *
   * @var int
   */
  const MAX_CODE_LENGTH = 255;

  /**
   * The coupon code generator.
   *
   * @var \Drupal\commerce_promotion\CouponCodeGeneratorInterface
   */
  protected $couponCodeGenerator;

  /**
   * The promotion.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface
   */
  protected $promotion;

  /**
   * Constructs a new CouponGenerateForm object.
   *
   * @param \Drupal\commerce_promotion\CouponCodeGeneratorInterface $coupon_code_generator
   *   The coupon code generator.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CouponCodeGeneratorInterface $coupon_code_generator, CurrentRouteMatch $current_route_match) {
    $this->couponCodeGenerator = $coupon_code_generator;
    $this->promotion = $current_route_match->getParameter('commerce_promotion');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_promotion.coupon_code_generator'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_coupon_generate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of coupons'),
      '#required' => TRUE,
      '#default_value' => '10',
      '#min' => 1,
      '#max' => 1000,
      '#step' => 1,
    ];
    $form['pattern'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Coupon code pattern'),
    ];
    $form['pattern']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#required' => TRUE,
      '#options' => [
        'alphanumeric' => $this->t('Alphanumeric'),
        'alphabetic' => $this->t('Alphabetic'),
        'numeric' => $this->t('Numeric'),
      ],
      '#default_value' => 'alphanumeric',
    ];
    $form['pattern']['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#size' => 20,
    ];
    $form['pattern']['suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Suffix'),
      '#size' => 20,
    ];
    $form['pattern']['length'] = [
      '#type' => 'number',
      '#title' => $this->t('Length'),
      '#description' => $this->t('Length does not include prefix/suffix.'),
      '#required' => TRUE,
      '#default_value' => 8,
      '#min' => 1,
    ];
    $form['limit'] = [
      '#type' => 'radios',
      '#title' => $this->t('Number of uses per coupon'),
      '#options' => [
        0 => $this->t('Unlimited'),
        1 => $this->t('Limited number of uses'),
      ],
      '#default_value' => 1,
    ];
    $form['usage_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of uses'),
      '#title_display' => 'invisible',
      '#default_value' => 1,
      '#min' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="limit"]' => ['value' => 0],
        ],
      ],
    ];
    $form['limit_customer'] = [
      '#type' => 'radios',
      '#title' => $this->t('Number of uses per customer per coupon'),
      '#options' => [
        0 => $this->t('Unlimited'),
        1 => $this->t('Limited number of uses'),
      ],
      '#default_value' => 1,
    ];
    $form['usage_limit_customer'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of uses per customer'),
      '#title_display' => 'invisible',
      '#default_value' => 1,
      '#min' => 1,
      '#states' => [
        'invisible' => [
          ':input[name="limit_customer"]' => ['value' => 0],
        ],
      ],
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Make sure that the total length doesn't exceed the database limit.
    $code_length = strlen($values['prefix']) + strlen($values['suffix']) + $values['length'];
    if ($code_length > self::MAX_CODE_LENGTH) {
      $form_state->setError($form['pattern'], $this->t('The total pattern length (@coupon_length) exceeds the maximum length allowed (@max_length).', [
        '@coupon_length' => $code_length,
        '@max_length' => self::MAX_CODE_LENGTH,
      ]));
    }

    // Validate that pattern for the given quantity.
    $quantity = $values['quantity'];
    $pattern = new CouponCodePattern($values['format'], $values['prefix'], $values['suffix'], $values['length']);
    if (!$this->couponCodeGenerator->validatePattern($pattern, $quantity)) {
      $form_state->setError($form['pattern'], $this->t('This pattern cannot be used to generate @quantity coupons.', [
        '@quantity' => $quantity,
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $quantity = $values['quantity'];
    $coupon_values = [
      'promotion_id' => $this->promotion->id(),
      'usage_limit' => $values['limit'] ? $values['usage_limit'] : 0,
      'usage_limit_customer' => $values['usage_limit_customer'],
    ];
    $pattern = new CouponCodePattern($values['format'], $values['prefix'], $values['suffix'], $values['length']);

    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Generating coupons'))
      ->setProgressMessage('')
      ->setFinishCallback([$this, 'finishBatch'])
      ->addOperation([get_class($this), 'processBatch'], [$quantity, $coupon_values, $pattern]);
    batch_set($batch_builder->toArray());

    $form_state->setRedirect('entity.commerce_promotion_coupon.collection', [
      'commerce_promotion' => $this->promotion->id(),
    ]);
  }

  /**
   * Processes the batch and generates the coupons.
   *
   * @param int $quantity
   *   The number of coupons to generate.
   * @param string[] $coupon_values
   *   The initial coupon entity values.
   * @param \Drupal\commerce_promotion\CouponCodePattern $pattern
   *   The pattern.
   * @param array $context
   *   The batch context information.
   */
  public static function processBatch($quantity, array $coupon_values, CouponCodePattern $pattern, array &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['total_quantity'] = (int) $quantity;
      $context['sandbox']['created'] = 0;
      $context['results']['codes'] = [];
      $context['results']['total_quantity'] = $quantity;
    }

    $total_quantity = $context['sandbox']['total_quantity'];
    $created = &$context['sandbox']['created'];
    $remaining = $total_quantity - $created;

    $coupon_storage = \Drupal::entityTypeManager()->getStorage('commerce_promotion_coupon');
    $limit = ($remaining < self::BATCH_SIZE) ? $remaining : self::BATCH_SIZE;
    $coupon_code_generator = \Drupal::service('commerce_promotion.coupon_code_generator');
    $codes = $coupon_code_generator->generateCodes($pattern, $limit);
    if (!empty($codes)) {
      foreach ($codes as $code) {
        $coupon = $coupon_storage->create([
          'code' => $code,
        ] + $coupon_values);
        $coupon->save();
        $context['results']['codes'][] = $code;
        $created++;
      }
      $context['message'] = t('Creating coupon @created of @total_quantity', [
        '@created' => $created,
        '@total_quantity' => $total_quantity,
      ]);
      $context['finished'] = $created / $total_quantity;
    }
    else {
      $context['finished'] = 1;
    }
  }

  /**
   * Batch finished callback: display batch statistics.
   *
   * @param bool $success
   *   Indicates whether the batch has completed successfully.
   * @param mixed[] $results
   *   The array of results gathered by the batch processing.
   * @param string[] $operations
   *   If $success is FALSE, contains the operations that remained unprocessed.
   */
  public static function finishBatch($success, array $results, array $operations) {
    if ($success) {
      $created = count($results['codes']);
      // An incomplete set of coupons was generated.
      if ($created != $results['total_quantity']) {
        \Drupal::messenger()->addWarning(t('Generated %created out of %total requested coupons. Consider adding a unique prefix/suffix or increasing the pattern length to improve results.', [
          '%created' => $created,
          '%total' => $results['total_quantity'],
        ]));
      }
      else {
        \Drupal::messenger()->addMessage(\Drupal::translation()->formatPlural(
          $created,
          'Generated 1 coupon.',
          'Generated @count coupons.'
        ));
      }
    }
    else {
      $error_operation = reset($operations);
      \Drupal::messenger()->addError(t('An error occurred while processing @operation with arguments: @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ]));
    }
  }

}
