<?php

/**
 * @file
 * Builds placeholder replacement tokens for order data.
 */

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;

/**
 * Implements hook_token_info().
 */
function commerce_order_token_info() {
  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type = $entity_type_manager->getDefinition('commerce_order');
  assert($entity_type !== NULL);
  $info = [];

  $info['tokens']['commerce_order']['url'] = [
    'name' => t('URL'),
    'description' => t('The URL of the order.'),
  ];
  $info['tokens']['commerce_order']['admin-url'] = [
    'name' => t('URL'),
    'description' => t('The URL for administrators to view the order.'),
  ];

  return $info;
}

/**
 * Implements hook_tokens().
 */
function commerce_order_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type === 'commerce_order' && !empty($data['commerce_order'])) {
    $url_options = ['absolute' => TRUE];
    if (isset($options['langcode'])) {
      $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    }

    $order = $data['commerce_order'];
    assert($order instanceof OrderInterface);

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'url':
          $url = Url::fromRoute('entity.commerce_order.user_view', [
            'commerce_order' => $order->id(),
            'user' => $order->getCustomerId(),
          ], $url_options)->toString(TRUE);
          assert($url instanceof GeneratedUrl);
          $bubbleable_metadata->addCacheableDependency($url);
          $replacements[$original] = $url->getGeneratedUrl();
          break;

        case 'admin-url':
          $url = $order->toUrl('canonical', $url_options)->toString(TRUE);
          assert($url instanceof GeneratedUrl);
          $bubbleable_metadata->addCacheableDependency($url);
          $replacements[$original] = $url->getGeneratedUrl();
          break;
      }
    }
  }

  return $replacements;
}
