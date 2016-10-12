<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the billing information pane.
 *
 * @CommerceCheckoutPane(
 *   id = "billing_information",
 *   label = @Translation("Billing information"),
 *   default_step = "order_information",
 *   wrapper_element = "fieldset",
 * )
 */
class BillingInformation extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new BillingInformation object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface $checkout_flow
   *   The parent checkout flow.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $checkout_flow);

    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    $summary = '';
    if ($billing_profile = $this->order->getBillingProfile()) {
      $profile_view_builder = $this->entityTypeManager->getViewBuilder('profile');
      $summary = $profile_view_builder->view($billing_profile, 'default');
      $summary = $this->renderer->render($summary);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $billing_profile = $this->order->getBillingProfile();
    if (!$billing_profile) {
      $profile_storage = $this->entityTypeManager->getStorage('profile');
      $billing_profile = $profile_storage->create([
        'type' => 'customer',
        'uid' => $this->order->getCustomerId(),
      ]);
    }
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->buildForm($billing_profile, $pane_form, $form_state);
    // Remove the details wrapper from the address field.
    if (!empty($pane_form['address']['widget'][0])) {
      $pane_form['address']['widget'][0]['#type'] = 'container';
    }
    // Store the billing profile for the validate/submit methods.
    $pane_form['#billing_profile'] = $billing_profile;

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $billing_profile = clone $pane_form['#billing_profile'];
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->extractFormValues($billing_profile, $pane_form, $form_state);
    $form_display->validateFormValues($billing_profile, $pane_form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $billing_profile = clone $pane_form['#billing_profile'];
    $form_display = EntityFormDisplay::collectRenderDisplay($billing_profile, 'default');
    $form_display->extractFormValues($billing_profile, $pane_form, $form_state);
    $billing_profile->save();
    $this->order->setBillingProfile($billing_profile);
  }

}
