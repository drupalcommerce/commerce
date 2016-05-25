<?php

/**
 * @file
 * Builds placeholder replacement tokens for commerce product-related data.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\user\Entity\User;

use Drupal\commerce_product\Entity\ProductType;

/**
 * Implements hook_token_info().
 */
function commerce_product_token_info() {
  $type = array(
    'name' => t('Commerce product'),
    'description' => t('Tokens related to commerce products.'),
    'needs-data' => 'commerce_product',
  );

  // Core tokens for commerce products
  $e['product_id'] = array(
    'name' => t("Product ID"),
    'description' => t('The unique ID of the commerce product.'),
  );
  $e['type'] = array(
    'name' => t("Product type"),
  );
  $e['type-name'] = array(
    'name' => t("Product type name"),
    'description' => t("The human-readable name of the commerce product type."),
  );
  $e['title'] = array(
    'name' => t("Title"),
  );
  $e['body'] = array(
    'name' => t("Body"),
    'description' => t("The main body text of the commerce product."),
  );
  $e['summary'] = array(
    'name' => t("Summary"),
    'description' => t("The summary of the commerce product's main body text."),
  );
  $e['langcode'] = array(
    'name' => t('Language code'),
    'description' => t('The language code of the language the commerce product is written in.'),
  );
  $e['url'] = array(
    'name' => t("URL"),
    'description' => t("The URL of the commerce product."),
    'type' => 'url',
  );
  $e['edit-url'] = array(
    'name' => t("Edit URL"),
    'description' => t("The URL of the commerce product's edit page."),
  );

  // Chained tokens for commerce products.
  $e['created'] = array(
    'name' => t("Date created"),
    'type' => 'date',
  );
  $e['changed'] = array(
    'name' => t("Date changed"),
    'description' => t("The date the commerce product was most recently updated."),
    'type' => 'date',
  );
  $e['author'] = array(
    'name' => t("Author"),
    'type' => 'user',
  );

    return [
        'types' => [
            'commerce_product' => $type,
        ],
        'tokens' => [
            'commerce_product' => $e,
        ],
    ];
}

/**
 * Implements hook_tokens().
 */
function commerce_product_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $url_options = array('absolute' => TRUE);
  if (isset($options['langcode'])) {
    $url_options['language'] = \Drupal::languageManager()->getLanguage($options['langcode']);
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  $replacements = [];

  if ($type == 'commerce_product' && !empty($data['commerce_product'])) {
    /** @var \Drupal\commerce_product\Entity\Product $e */
    $e = $data['commerce_product'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'product_id':
          $replacements[$original] = $e->id();
          break;
        case 'type':
          $replacements[$original] = $e->bundle();
          break;
        case 'type-name':
          $replacements[$original] = ProductType::load($e->bundle())->label();
          break;
        case 'title':
          $replacements[$original] = $e->getTitle();
          break;
        case 'body':
        case 'summary':
          $translation = \Drupal::entityManager()->getTranslationFromContext($e, $langcode, array('operation' => 'commerce_product_tokens'));
          if ($translation->hasField('body') && ($items = $translation->get('body')) && !$items->isEmpty()) {
            $item = $items[0];
            // If the summary was requested and is not empty, use it.
            if ($name == 'summary' && !empty($item->summary)) {
              $output = $item->summary_processed;
            }
            // Attempt to provide a suitable version of the 'body' field.
            else {
              $output = $item->processed;
              // A summary was requested.
              if ($name == 'summary') {
                // Generate an optionally trimmed summary of the body field.

                // Get the 'trim_length' size used for the 'teaser' mode, if
                // present, or use the default trim_length size.
                $display_options = entity_get_display('commerce_product', $e->bundle(), 'teaser')->getComponent('body');
                if (isset($display_options['settings']['trim_length'])) {
                  $length = $display_options['settings']['trim_length'];
                }
                else {
                  $settings = \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('text_summary_or_trimmed');
                  $length = $settings['trim_length'];
                }

                $output = text_summary($output, $item->format, $length);
              }
            }
            // "processed" returns a \Drupal\Component\Render\MarkupInterface
            // via check_markup().
            $replacements[$original] = $output;
          }
          break;
        case 'langcode':
          $replacements[$original] = $e->language()->getId();
          break;
        case 'url':
          $replacements[$original] = $e->url('canonical', $url_options);
          break;
        case 'edit-url':
          $replacements[$original] = $e->url('edit-form', $url_options);
          break;
        case 'author':
          $account = $e->getOwner() ? $e->getOwner() : User::load(0);
          $bubbleable_metadata->addCacheableDependency($account);
          $replacements[$original] = $account->label();
          break;
        case 'created':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($e->getCreatedTime(), 'medium', '', NULL, $langcode);
          break;
        case 'changed':
          $date_format = DateFormat::load('medium');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = format_date($e->getChangedTime(), 'medium', '', NULL, $langcode);
          break;
      }
    }

    if ($author_tokens = $token_service->findWithPrefix($tokens, 'author')) {
      $replacements += $token_service->generate('user', $author_tokens, array('user' => $e->getOwner()), $options, $bubbleable_metadata);
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'created')) {
      $replacements += $token_service->generate('date', $created_tokens, array('date' => $e->getCreatedTime()), $options, $bubbleable_metadata);
    }

    if ($changed_tokens = $token_service->findWithPrefix($tokens, 'changed')) {
      $replacements += $token_service->generate('date', $changed_tokens, array('date' => $e->getChangedTime()), $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
