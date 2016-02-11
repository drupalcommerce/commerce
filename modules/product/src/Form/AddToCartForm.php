<?php
namespace Drupal\commerce_product\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\StoreContextInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the add to cart form for product variations.
 */
class AddToCartForm extends FormBase {

  /**
   * The cart manager.
   *
   * @var \Drupal\commerce_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The cart provider.
   *
   * @var \Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * The current variation.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $currentVariation;

  /**
   * Constructs a new AddToCartForm object.
   *
   * @param \Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The cart manager.
   * @param \Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The cart provider.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_store\StoreContextInterface $store_context
   *   The store context.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager, StoreContextInterface $store_context) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->entityTypeManager = $entity_type_manager;
    $this->storeContext = $store_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager'),
      $container->get('commerce_store.store_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_product_add_to_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $product = NULL, array $settings = NULL) {
    $wrapper_id = Html::getUniqueId('commerce-product-add-to-cart-form');
    $form += [
      '#tree' => TRUE,
      '#product' => $product,
      '#settings' => $settings,
      '#wrapper_id' => $wrapper_id,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];
    $form = $this->variationElement($form, $form_state);
    $form['quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Quantity'),
      '#default_value' => $settings['default_quantity'],
      '#min' => 1,
      '#max' => 9999,
      '#step' => 1,
      '#access' => !empty($settings['show_quantity']),
      '#weight' => 99,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
      '#weight' => 100,
    ];
    if ($form['variation']['#value'] === 0) {
      $form['submit']['#value'] = $this->t('Not available');
      $form['submit']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variation_storage = $this->entityTypeManager->getStorage('commerce_product_variation');
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation = $variation_storage->load($form_state->getValue('variation'));
    $available_stores = $variation->getProduct()->getStores();
    if (count($available_stores) === 1) {
      $store = reset($available_stores);
    }
    else {
      $store = $this->storeContext->getStore();
      if (!in_array($store, $available_stores)) {
        // Throw an exception.
      }
    }
    // @todo The order type should not be hardcoded.
    $cart = $this->cartProvider->getCart('default', $store);
    if (!$cart) {
      $cart = $this->cartProvider->createCart('default', $store);
    }
    $quantity = $form_state->getValue('quantity');
    $combine = $form['#settings']['combine'];
    $this->cartManager->addEntity($cart, $variation, $quantity, $combine);
    drupal_set_message(t('@variation added to @cart-link.', [
      '@variation' => $variation->label(),
      '@cart-link' => Link::createFromRoute('your cart', 'commerce_cart.page')->toString(),
    ]));
  }

  /**
   * Builds the form elements for selecting a variation.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The modified form.
   */
  protected function variationElement(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $product = $form['#product'];
    $variations = $product->variations->referencedEntities();
    if (count($variations) === 0) {
      // Signal to the parent form that there are no variations to select.
      $form['variation'] = [
        '#type' => 'value',
        '#value' => 0,
      ];
      return $form;
    }
    elseif (count($variations) === 1) {
      // Preselect the only possible variation.
      // @todo Limit this behavior to products with no attributes instead.
      $selected_variation = reset($variations);
      $form['variation'] = [
        '#type' => 'value',
        '#value' => $selected_variation->id(),
      ];
      return $form;
    }

    // Build the full attribute form.
    $selected_variation = $this->selectVariationFromUserInput($variations, $form_state);
    $form['variation'] = [
      '#type' => 'value',
      '#value' => $selected_variation->id(),
    ];
    $form['attributes'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['attribute-widgets'],
      ],
    ];
    foreach ($this->getAttributeInfo($selected_variation, $variations) as $field_name => $attribute) {
      $form['attributes'][$field_name] = [
        '#type' => $attribute['type'],
        '#title' => $attribute['title'],
        '#options' => $attribute['values'],
        '#required' => $attribute['required'],
        '#default_value' => $selected_variation->get($field_name)->target_id,
        '#ajax' => [
          'callback' => '::ajaxRefresh',
          'wrapper' => $form['#wrapper_id'],
        ],
      ];
      // Convert the _none option into #empty_value.
      if (isset($form['attributes'][$field_name]['options']['_none'])) {
        if (!$form['attributes'][$field_name]['#required']) {
          $form['attributes'][$field_name]['#empty_value'] = '';
        }
        unset($form['attributes'][$field_name]['options']['_none']);
      }
      // 1 required value -> Disable the element to skip unneeded ajax calls.
      if ($attribute['required'] && count($attribute['values']) === 1) {
        $form['attributes'][$field_name]['#disabled'] = TRUE;
      }
    }

    return $form;
  }

  /**
   * Ajax callback.
   */
  public function ajaxRefresh(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Selects a product variation based on user input containing attribute values.
   *
   * If there's no user input (form viewed for the first time), the default
   * variation is returned.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   An array of product variations.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The selected variation.
   */
  protected function selectVariationFromUserInput(array $variations, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    $current_variation = reset($variations);
    if (!empty($user_input)) {
      $attributes = $user_input['attributes'];
      foreach ($variations as $variation) {
        $match = TRUE;
        foreach ($attributes as $field_name => $value) {
          if ($variation->get($field_name)->target_id != $value) {
            $match = FALSE;
          }
        }

        if ($match) {
          $current_variation = $variation;
          break;
        }
      }
    }

    return $current_variation;
  }

  /**
   * Gets the attribute information for the selected product variation.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $selected_variation
   *   The selected product variation.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The available product variations.
   *
   * @return array[]
   *   The attribute information, keyed by field name.
   */
  protected function getAttributeInfo(ProductVariationInterface $selected_variation, array $variations) {
    $attributes = [];
    /** @var \Drupal\Core\Field\FieldConfigInterface[] $field_definitions */
    $field_definitions = $selected_variation->getAttributeFieldDefinitions();
    $field_names = array_keys($field_definitions);
    $index = 0;
    foreach ($field_definitions as $field) {
      $field_name = $field->getName();
      $third_party_settings = $field->getThirdPartySettings('commerce_product');
      if (!empty($third_party_settings['attribute_widget_title'])) {
        $attribute_label = $third_party_settings['attribute_widget_title'];
      }
      else {
        $attribute_label = $field->label();
      }

      $attributes[$field_name] = [
        'field_name' => $field_name,
        'type' => $third_party_settings['attribute_widget'],
        'title' => $attribute_label,
        'required' => $field->isRequired(),
      ];
      // The first attribute gets all values. Every next attribute gets only
      // the values from variations matching the previous attribute value.
      // For 'Color' and 'Size' attributes that means getting the colors of all
      // variations, but only the sizes of variations with the selected color.
      $callback = NULL;
      if ($index > 0) {
        $previous_field_name = $field_names[$index - 1];
        $previous_field_value = $selected_variation->get($previous_field_name)->target_id;
        $callback = function ($variation) use ($previous_field_name, $previous_field_value) {
          return $variation->get($previous_field_name)->target_id == $previous_field_value;
        };
      }

      $attributes[$field_name]['values'] = $this->getAttributeValues($variations, $field_name, $callback);
      $index++;
    }
    // Filter out attributes with no values.
    $attributes = array_filter($attributes, function ($attribute) {
      return !empty($attribute['values']);
    });

    return $attributes;
  }

  /**
   * Gets the attribute values of a given set of variations.
   *
   * @param array $variations
   *   The variations.
   * @param string $field_name
   *   The field name of the attribute.
   * @param callable|NULL $callback
   *   An optional callback to use for filtering the list.
   *
   * @return array[]
   *   The attribute values, keyed by attribute id.
   */
  protected function getAttributeValues(array $variations, $field_name, callable $callback = NULL) {
    $values = [];
    foreach ($variations as $variation) {
      if (is_null($callback) || call_user_func($callback, $variation)) {
        if (!$variation->get($field_name)->isEmpty()) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $attribute_value */
          $attribute_value = $variation->get($field_name)->entity;
          $values[$attribute_value->id()] = $attribute_value->label();
        }
        else {
          $values['_none'] = '';
        }
      }
    }

    return $values;
  }

}
