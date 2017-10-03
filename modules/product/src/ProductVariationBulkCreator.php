<?php

namespace Drupal\commerce_product;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\Product;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Markup;
use Drupal\commerce_price\Price;
use Drupal\Component\Utility\NestedArray;

/**
 * Default implementation of the ProductVariationBulkCreatorInterface.
 */
class ProductVariationBulkCreator implements ProductVariationBulkCreatorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductVariationBulkCreator object.
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
  public static function getSkuwidget(ProductVariation $variation) {
    $form_display = entity_get_form_display($variation->getEntityTypeId(), $variation->bundle(), 'default');

    return $form_display->getRenderer('sku');
  }

  /**
   * {@inheritdoc}
   */
  public static function getSkuSettings(ProductVariation $variation) {
    /** @var Drupal\commerce_product\Plugin\Field\FieldWidget\ProductVariationSkuWidget $widget */
    /** @var Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget $widget */
    $widget = static::getSkuwidget($variation);
    // If no one widget is enabled, then we need to asign uniqid() SKUs at the
    // background to avoid having variations without SKU at all.
    $default_sku_settings = [
      'uniqid_enabled' => TRUE,
      'more_entropy' => FALSE,
      'prefix' => 'default_sku-',
      'suffix' => '',
    ];

    return $widget ? $widget->getSettings() : $default_sku_settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function getAutoSku(ProductVariation $variation) {
    extract(static::getSkuSettings($variation));

    // Do return empty string in case of StringTextfieldWidget.
    return isset($uniqid_enabled) ? ($uniqid_enabled ? \uniqid($prefix, $more_entropy) . $suffix : "{$prefix}{$suffix}") : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuildPreRenderArrayAlter(array $element) {
    $i = 0;
    while (isset($element['alter_data_' . $i]) && $data = $element['alter_data_' . $i]) {
      $parents = [];
      if (isset($data['#parents'])) {
        $parents = $data['#parents'];
        unset($data['#parents']);
      }
      unset($element['alter_data_' . $i]);
      $key_exists = NULL;
      $old_data = NestedArray::getValue($element, $parents, $key_exists);
      if (is_array($old_data)) {
        $data = array_replace($old_data, $data);
      }
      elseif ($key_exists && !in_array($old_data, $data)) {
        $data[] = $old_data;
      }
      NestedArray::setValue($element, $parents, $data);
      $i++;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductVariation(Product $product) {
    $variations = $product->getVariations();
    $variation = end($variations);
    $timestamp = time();
    if (!$variation instanceof ProductVariation) {
      $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->create([
        'type' => $product->getFieldDefinition('variations')->getSettings()['handler_settings']['target_bundles'][0],
        'created' => $timestamp,
        'changed' => $timestamp,
      ]);
    }

    return $variation;
  }

  /**
   * {@inheritdoc}
   */
  public function createProductVariation(Product $product, array $variation_custom_values = []) {
    $variation = $this->getProductVariation($product);
    $field = $this->getAttributeFieldOptionIds($variation);
    if ($all = $this->getAttributesCombinations([$variation])) {
      foreach (reset($all['not_used_combinations']) as $field_name => $id) {
        $variation->get($field_name)->setValue(['target_id' => $id == '_none' ? NULL : $id]);
      }
    }
    $sku = static::getAutoSku($variation);
    $variation->setSku(empty($sku) ? \uniqid() : $sku);

    foreach ($variation_custom_values as $name => $value) {
      $variation->set($name, $value);
    }
    if (!$variation->getPrice() instanceof Price) {
      $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');
      $currencies = array_keys($currency_storage->loadMultiple());
      $currency = empty($currencies) ? 'USD' : $currencies[0];
      // Decimals are omitted intentionally as $currency format is unknown here.
      // The prices still will have valid format after saving.
      $variation->setPrice(new Price('1', $currency));
    }
    $variation->updateOriginalValues();

    return $variation;
  }

  /**
   * {@inheritdoc}
   */
  public function createAllProductVariations(Product $product, array $variation_custom_values = []) {
    $variations = $product->getVariations();
    $timestamp = time();
    if (empty($variations) || !empty($variation_custom_values)) {
      $variations[] = $this->createProductVariation($product, $variation_custom_values);
      $timestamp--;
    }

    if (!$all = $this->getAttributesCombinations($variations)) {
      return;
    }

    // Improve perfomance by getting sku settings just once instead of
    // calling static::getAutoSku() in the loop.
    extract(static::getSkuSettings($all['last_variation']));
    $prefix = isset($prefix) ? $prefix : '';
    $suffix = isset($suffix) ? $suffix : '';
    $more_entropy = isset($more_entropy) ? $more_entropy : FALSE;
    foreach ($all['not_used_combinations'] as $combination) {
      $variation = $all['last_variation']->createDuplicate()
        ->setSku(\uniqid($prefix, $more_entropy) . $suffix)
        ->setChangedTime($timestamp)
        ->setCreatedTime($timestamp);
      foreach ($combination as $field_name => $id) {
        $variation->get($field_name)->setValue(['target_id' => $id == '_none' ? NULL : $id]);
      }
      $variation->updateOriginalValues();
      $variations[] = $variation;
      // To avoid the same CreatedTime on multiple variations decrease the
      // $timestamp by one second instead of calling time() in the loop.
      $timestamp--;
    }

    return $variations;
  }

  /**
   * {@inheritdoc}
   */
  public function createAllIefFormVariations(array $form, FormStateInterface $form_state) {
    // Rid of entity type manager here as that prevents to use instance of
    // ProductVariationBulkCreator as an AJAX callback therefore forcing to use
    // just the class name instead of object and define all functions as static.
    $this->entityTypeManager = NULL;
    $ief_id = $form['variations']['widget']['#ief_id'];
    $ief_entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']) ?: [];
    if (!$all = $this->getAttributesCombinations(array_column($ief_entities, 'entity'))) {
      return;
    }
    // The attributes (ids and options) may be quite heavy, so unset them.
    unset($all['attributes']);
    $timestamp = time();
    $ief_entity = end($ief_entities);
    extract(static::getSkuSettings($all['last_variation']));
    $prefix = isset($prefix) ? $prefix : '';
    $suffix = isset($suffix) ? $suffix : '';
    $more_entropy = isset($more_entropy) ? $more_entropy : FALSE;
    foreach ($all['not_used_combinations'] as $combination) {
      $variation = $all['last_variation']->createDuplicate()
        ->setSku(\uniqid($prefix, $more_entropy) . $suffix)
        ->setChangedTime($timestamp)
        ->setCreatedTime($timestamp);
      foreach ($combination as $field_name => $id) {
        $variation->get($field_name)->setValue(['target_id' => $id == '_none' ? NULL : $id]);
      }
      $variation->updateOriginalValues();
      $ief_entity['entity'] = $variation;
      $ief_entity['weight'] += 1;
      $ief_entity['needs_save'] = TRUE;
      array_push($ief_entities, $ief_entity);
      $timestamp--;
    }
    // Before continuing unset $all['*combinations'] which might be a huge data.
    unset($all);
    $form_state->set(['inline_entity_form', $ief_id, 'entities'], $ief_entities);
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function getIefFormNotUsedAttributesCombination(FormStateInterface $form_state, $ief_id = '') {
    $this->entityTypeManager = NULL;
    $ief_entities = $form_state->get(['inline_entity_form', $ief_id, 'entities']) ?: [];

    return $this->getNotUsedAttributesCombination(array_column($ief_entities, 'entity'));
  }

  /**
   * {@inheritdoc}
   */
  public function getNotUsedAttributesCombination(array $variations) {
    if (!$all = $this->getDuplicationsHtmlList($variations)) {
      return;
    }
    $all['not_used_combination'] = reset($all['not_used_combinations']);
    // Rid of unecessary data which might be quite heavy.
    unset($all['used_combinations'], $all['not_used_combinations'], $all['attributes']);

    return $all;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsedAttributesCombinations(array $variations) {
    $all = [];
    $all['duplicated'] = $all['used_combinations'] = [];
    $all['last_variation'] = end($variations);
    $all['attributes'] = $this->getAttributeFieldOptionIds(end($variations));
    $nones = array_fill_keys(array_keys($all['attributes']['ids']), '_none');
    foreach ($variations as $index => $variation) {
      // ProductVariation->getAttributeValueIds() does not return empty optional
      // fields. Merge 'field_name' => '_none' as a choice in the combination.
      // @todo Render '_none' option on an Add to Cart form.
      // @see ProductVariationAttributesWidget->formElement()
      // @see CommerceProductRenderedAttribute::processRadios()
      $combination = array_merge($nones, $variation->getAttributeValueIds());
      if (in_array($combination, $all['used_combinations'])) {
        $all['duplicated'][$index] = $combination;
      }
      else {
        $all['used_combinations'][$index] = $combination;
      }
    }
    $all['used'] = count($all['used_combinations']);
    $all['count'] = $all['attributes']['count'];

    return $all;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuplicationsHtmlList(array $variations) {
    if (!$all = $this->getAttributesCombinations($variations)) {
      return;
    }
    if (!empty($all['duplicated'])) {
      $all['duplications_list'] = '<ul>';
      foreach ($all['duplicated'] as $fields) {
        $label = [];
        foreach ($fields as $field_name => $id) {
          if (isset($all['attributes']['options'][$field_name][$id])) {
            $label[] = $all['attributes']['options'][$field_name][$id];
          }
        }
        $label = Html::escape(implode(', ', $label));
        $all['duplications_list'] .= '<li>' . $label . '</li>';
      }
      $all['duplications_list'] .= '</ul>';
      $all['duplications_list'] = Markup::create($all['duplications_list']);
    }
    $all['duplicated'] = count($all['duplicated']);

    return $all;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributesCombinations(array $variations, array $return = ['not_all' => 500]) {
    $all = $this->getUsedAttributesCombinations($variations);
    // Restrict by default the number of returned not used combinations if their
    // number exceeds some resonable number (500). To get all possible
    // combinations call this method with an empty array as the second argument.
    if (isset($return['not_all']) && $all['count'] > $return['not_all']) {
      $all += $return;
      $all['used_combinations']['not_all'] = $return['not_all'];
    }
    $all['not_used_combinations'] = $this->getArrayValueCombinations($all['attributes']['ids'], $all['used_combinations']);
    unset($all['used_combinations']['not_all']);
    $all['not_used'] = count($all['not_used_combinations']);

    return $all;
  }

  /**
   * {@inheritdoc}
   */
  public function getArrayValueCombinations(array $data = [], array $exclude = [], &$all = [], $group = [], $value = NULL, $i = 0, $k = NULL, $c = NULL, $f = NULL) {
    $keys = $k ?: array_keys($data);
    $count = $c ?: count($data);
    if ($include = isset($value) === TRUE) {
      $group[$f] = $value;
    }
    if ($i >= $count && $include) {
      foreach ($exclude as $index => $combination) {
        if ($group == $combination) {
          unset($exclude[$index]);
          $include = FALSE;
          break;
        }
      }
      if ($include) {
        $all[] = $group;
      }
    }
    elseif (isset($keys[$i])) {
      if (isset($exclude['not_all']) && !empty($all) && count($all) > $exclude['not_all']) {
        return $all;
      }
      $field_name = $keys[$i];
      foreach ($data[$field_name] as $key => $val) {
        $this->getArrayValueCombinations($data, $exclude, $all, $group, $val, $i + 1, $keys, $count, $field_name);
      }
    }

    return $all;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeFieldOptionIds(ProductVariation $variation) {
    $count = 1;
    $field_options = $fields = $ids = $options = [];
    foreach ($this->getAttributeFieldNames($variation) as $field_name) {
      $definition = $variation->get($field_name)->getFieldDefinition();
      $fields[$field_name] = $definition->getFieldStorageDefinition()
        ->getOptionsProvider('target_id', $variation)
        ->getSettableOptions(\Drupal::currentUser());
      $ids[$field_name] = $options[$field_name] = [];
      foreach ($fields[$field_name] as $key => $value) {
        if (is_array($value) && $keys = array_keys($value)) {
          $ids[$field_name] = array_unique(array_merge($ids[$field_name], $keys));
          $options[$field_name] += $value;
        }
        elseif ($keys = array_keys($fields[$field_name])) {
          $ids[$field_name] = array_unique(array_merge($ids[$field_name], $keys));
          $options[$field_name] += $fields[$field_name];
        }
        // Optional fields need '_none' id as a possible choice.
        !$definition->isRequired() && !in_array('_none', $ids[$field_name]) && array_unshift($ids[$field_name], '_none');
        array_walk($ids[$field_name], function (&$id) {
          $id = (string) $id;
        });
      }
      $count *= count($ids[$field_name]);
    }
    $field_options['ids'] = $ids;
    $field_options['options'] = $options;
    $field_options['count'] = $count;

    return $field_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getAttributeFieldNames(ProductVariation $variation) {
    $attribute_field_manager = \Drupal::service('commerce_product.attribute_field_manager');
    $field_map = $attribute_field_manager->getFieldMap($variation->bundle());

    return array_unique(array_column($field_map, 'field_name'));
  }

}
