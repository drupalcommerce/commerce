<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;
use Drupal\Core\Form\FormStateInterface;

/**
 * A base class for shared configuration of billing information panes.
 */
abstract class BillingInformationPaneBase extends CheckoutPaneBase implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'reuse_profile' => FALSE,
        'reuse_profile_label' => 'My shipping address is the same as my billing address.',
        'reuse_profile_default' => FALSE,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    if (!empty($this->configuration['reuse_profile'])) {
      $summary = $this->t('Allow reuse of shipping profile: Yes') . '<br>';
      $summary .= $this->t('Reuse shipping profile label: @label', [
        '@label' => $this->configuration['reuse_profile_label']
      ]) . '<br>';
      $summary .= $this->t('Reuse shipping profile by default: @default', [
        '@default' => ($this->configuration['reuse_profile_default'])
          ? $this->t('Yes')
          : $this->t('No')
      ]);
    }
    else {
      $summary = $this->t('Allow reuse of shipping profile: No');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $visible_state = [['.js-reuse-shipping-profile' => ['checked' => TRUE]]];

    $form['reuse_profile'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow reuse of shipping profile for billing'),
      '#default_value' => $this->configuration['reuse_profile'],
    ];
    $form['reuse_profile']['#attributes']['class'][] = 'js-reuse-shipping-profile';
    $form['reuse_profile_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reuse shipping profile label'),
      '#default_value' => $this->configuration['reuse_profile_label'],
      '#states' => [
        'visible' => $visible_state,
      ]
    ];
    $form['reuse_profile_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reuse shipping profile by default'),
      '#default_value' => $this->configuration['reuse_profile_default'],
      '#states' => [
        'visible' => $visible_state,
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['reuse_profile'] = !empty($values['reuse_profile']);
      $this->configuration['reuse_profile_label'] = $values['reuse_profile_label'];
      $this->configuration['reuse_profile_default'] = !empty($values['reuse_profile_default']);
    }
  }

  protected function getProfileSelectOptions() {
    $options = [];

    if (!empty($this->configuration['reuse_profile'])) {
      $reuse_label = !empty($this->configuration['reuse_profile_label'])
        ? $this->configuration['reuse_profile_label']
        : NULL;
      $reuse_default = isset($this->configuration['reuse_profile_default'])
        ? $this->configuration['reuse_profile_default']
        : FALSE;

      $options = [
        '#reuse_profile_label' => $reuse_label,
        '#reuse_profile_source' => 'commerce_order_get_shipping_profile',
        '#reuse_profile_default' => $reuse_default
      ];
    }

    return $options;
  }
}
