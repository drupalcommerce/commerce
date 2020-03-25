<?php

namespace Drupal\commerce_promotion\Plugin\views\area;

use Drupal\commerce\InlineFormManager;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\area\AreaPluginBase;
use Drupal\views\Plugin\views\argument\NumericArgument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a coupon redemption area handler.
 *
 * Shows the coupon redemption field with discounts listed in the footer of a
 * View.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsArea("commerce_coupon_redemption")
 */
class CouponRedemption extends AreaPluginBase {

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $orderStorage;

  /**
   * The inline form manager.
   *
   * @var \Drupal\commerce\InlineFormManager
   */
  protected $inlineFormManager;

  /**
   * Constructs a new OrderTotal instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce\InlineFormManager $inline_form_manager
   *   The inline form manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, InlineFormManager $inline_form_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->orderStorage = $entity_type_manager->getStorage('commerce_order');
    $this->inlineFormManager = $inline_form_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_inline_form')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['allow_multiple'] = ['default' => FALSE];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['empty']['#description'] = $this->t("Even if selected, this area handler will never render if a valid order cannot be found in the View's arguments.");

    $form['allow_multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow multiple coupons to be redeemed'),
      '#default_value' => $this->options['allow_multiple'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render($empty = FALSE) {
  }

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->view->argument as $name => $argument) {
      // First look for an order_id argument.
      if (!$argument instanceof NumericArgument) {
        continue;
      }
      if ($argument->getField() !== 'commerce_order.order_id') {
        continue;
      }
      $order = $this->orderStorage->load($argument->getValue());
      if (!$order) {
        continue;
      }
      $inline_form = $this->inlineFormManager->createInstance('coupon_redemption', [
        'order_id' => $order->id(),
        'max_coupons' => $this->options['allow_multiple'] ? NULL : 1,
      ]);

      // Workaround for core bug #2897377.
      $form['#id'] = Html::getId($form_state->getBuildInfo()['form_id']);
      $form['coupon_redemption'] = [
        '#type' => 'container',
        '#tree' => TRUE,
        '#weight' => $this->position,
        '#parents' => ['coupon_redemption'],
      ];
      $form['coupon_redemption'] = $inline_form->buildInlineForm($form['coupon_redemption'], $form_state);
      // InlineForm AJAX uses \Drupal\commerce\AjaxFormTrait::ajaxRefreshForm.
      // This re-renders the entire form and causes Views to break. We have
      // to override this AJAX definition so that we only return a subset
      // of the Views form.
      //
      // Without this, the order summary area can disappear _and_ the form
      // action becomes cart?ajax_form=1&_wrapper_format=html.
      //
      // @see \Drupal\commerce\AjaxFormTrait::ajaxRefreshForm
      // @see \Drupal\views\Form\ViewsForm::buildForm
      $form['coupon_redemption']['apply']['#ajax']['callback'] = [static::class, 'ajaxRefreshSummary'];
      if (isset($form['coupon_redemption']['coupons'])) {
        foreach ($form['coupon_redemption']['coupons'] as &$coupon) {
          $coupon['remove_button']['#ajax']['callback'] = [static::class, 'ajaxRefreshSummary'];
        }
      }
    }
  }

  /**
   * Method for the condition when view is empty.
   *
   * @param bool $empty
   *   Variable for view content exists or not.
   *
   * @return bool
   *   Returns boolean based on $empty.
   */
  public function viewsFormEmpty($empty = FALSE) {
    if (!$empty || !empty($this->options['empty'])) {
      return $this->options['empty'];
    }
    return $empty;
  }

  /**
   * Ajax callback for refreshing the order summary.
   */
  public static function ajaxRefreshSummary(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, -1);
    $inline_form = NestedArray::getValue($form, $parents);

    $order_storage = \Drupal::entityTypeManager()->getStorage('commerce_order');
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $order_storage->load($inline_form['#configuration']['order_id']);

    $order_total = $order->get('total_price')->view([
      'label' => 'hidden',
      'type' => 'commerce_order_total_summary',
    ]);
    $order_total['#prefix'] = '<div data-drupal-selector="order-total-summary">';
    $order_total['#suffix'] = '</div>';

    $response = new AjaxResponse();
    if (isset($order_total)) {
      $response->addCommand(new ReplaceCommand('[data-drupal-selector="order-total-summary"]', $order_total));
    }
    $response->addCommand(new InsertCommand('[data-drupal-selector="' . $inline_form['#attributes']['data-drupal-selector'] . '"]', $inline_form));
    $response->addCommand(new PrependCommand('[data-drupal-selector="' . $inline_form['#attributes']['data-drupal-selector'] . '"]', ['#type' => 'status_messages']));

    return $response;
  }

}
