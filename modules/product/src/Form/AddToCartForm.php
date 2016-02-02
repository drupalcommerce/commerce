<?php

/**
 * @file
 * Contains \Drupal\commerce_product\Form\AddToCartForm.
 */

namespace Drupal\commerce_product\Form;

use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_store\StoreContextInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
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
   * The variation storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $variationStorage;

  /**
   * The store context.
   *
   * @var \Drupal\commerce_store\StoreContextInterface
   */
  protected $storeContext;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $defaultVariation;

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
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(CartManagerInterface $cart_manager, CartProviderInterface $cart_provider, EntityTypeManagerInterface $entity_type_manager, StoreContextInterface $store_context, EntityFieldManagerInterface $entity_field_manager) {
    $this->cartManager = $cart_manager;
    $this->cartProvider = $cart_provider;
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->storeContext = $store_context;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('commerce_cart.cart_manager'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('entity_type.manager'),
      $container->get('commerce_store.store_context'),
      $container->get('entity_field.manager')
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
    /** @var \Drupal\commerce_product\Entity\Product $product */
    $form['#settings'] = $settings;
    $variations = $product->variations->referencedEntities();

    // Set the first variation as the default to be used.
    $this->defaultVariation = reset($variations);
    $form_state->set('default_variation', $this->defaultVariation->id());

    // If there are multiple variations, display ability to select a variation.
    if (count($variations) > 1) {
      $this->variationForm($form, $form_state, $variations);
    }

    $form['variation'] = [
      '#type' => 'value',
      '#value' => $form_state->get('default_variation'),
    ];

    if (!empty($settings['show_quantity'])) {
      $form['quantity'] = [
        '#type' => 'number',
        '#title' => $this->t('Quantity'),
        '#min' => 1,
        '#max' => 9999,
        '#step' => 1,
      ];
    }
    else {
      $form['quantity'] = [
        '#type' => 'value',
        '#value' => $settings['default_quantity'],
      ];
    }
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $variation = $this->variationStorage->load($form_state->getValue('variation'));
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
   * Builds the variation selection form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   An array of product variations.
   */
  protected function variationForm(array &$form, FormStateInterface $form_state, array $variations) {
    // Resolve the default variation from current attribute values.
    $this->resolveDefaultVariation($variations, $form_state);

    // Get qualified attribute options and add them to the form.
    foreach ($this->resolveQualifyingAttributes($variations) as $name => $data) {
      $this->attributeElement($form, $data, $data['used_options']);
    }

    if (!empty($form['attributes'])) {
      $form['attributes'] += [
        '#tree' => TRUE,
        '#prefix' => '<div id="attribute-widgets" class="attribute-widgets">',
        '#suffix' => '</div>',
        '#weight' => 0,
      ];
      $form['unchanged_attributes'] += [
        '#tree' => 'TRUE',
      ];
    }
  }

  /**
   * Adds an attribute form element to add to cart form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $attribute
   *   The attribute information array.
   * @param $used_options
   *   An array of options in use.
   */
  protected function attributeElement(array &$form, array $attribute, array $used_options) {
    $field_name = $attribute['name'];
    $options = array_intersect_key($attribute['options'], array_combine($used_options[$field_name], $used_options[$field_name]));
    $default_value = $this->defaultVariation->{$field_name}->first()->entity->id();

    $form['attributes'][$field_name] = [
      '#type' => $attribute['type'],
      '#title' => $attribute['title'],
      '#options' => $options,
      '#default_value' => $default_value,
      '#ajax' => [
        'callback' => '::attributesAjax',
        'wrapper' => 'attribute-widgets',
      ],
      '#value' => NULL,
    ];

    // Add the empty value if the field is not required and products on
    // the form include the empty value.
    if (!$attribute['required'] && in_array('', $used_options[$field_name])) {
      $form['attributes'][$field_name]['#empty_value'] = '';
    }

    $form['unchanged_attributes'][$field_name] = [
      '#type' => 'hidden',
      '#value' => $default_value,
    ];
  }

  /**
   * Gets an array of attributes and their settings.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   The product variations.
   *
   * @return array
   *   Information about the attributes.
   */
  protected function getAttributesInfo(array $variations) {
    $attributes = [];
    /** @var \Drupal\Core\Field\FieldConfigInterface $field */
    foreach ($this->defaultVariation->getAttributeFieldDefinitions() as $field) {
      $third_party_settings = $field->getThirdPartySettings('commerce_product');

      $attribute_label = !empty($third_party_settings['attribute_widget_title']) ?
        $third_party_settings['attribute_widget_title'] : $field->label();

      $attributes[$field->getName()] = [
        'name' => $field->getName(),
        'type' => $third_party_settings['attribute_widget'],
        'title' => $attribute_label,
        'required' => $field->isRequired(),
        'options' => [],
      ];

      // Get possible field options based on current variations.
      foreach ($variations as $variation) {
        $attribute_value = $variation->{$field->getName()}->first()->entity;
        $attributes[$field->getName()]['options'][$attribute_value->id()] = $attribute_value->label();
      }
    }
    return $attributes;
  }

  /**
   * Checks if a variation's attribute value matches expected value.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $variation
   *   The product variation.
   * @param $name
   *   The attribute field name.
   * @param $value
   *   The attribute value.
   *
   * @return bool
   *   Return TRUE on value match.
   */
  protected function isMatchingVariation(ProductVariationInterface $variation, $name, $value) {
    return $variation->{$name}->first()->entity->id() == $value;
  }

  /**
   * Resolves default product variation based on attribute values.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   An array of product variations.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function resolveDefaultVariation(array $variations, FormStateInterface $form_state) {
    $user_input = $form_state->getUserInput();
    if (empty($user_input)) {
      $this->defaultVariation = reset($variations);
    }
    else {
      $attribute_names = [];
      $unchanged_attributes = [];

      foreach ($variations as $variation) {
        // If the form state contains a set of attribute data, use it to try
        // and determine the default product.
        $changed_attribute = NULL;
        $match = TRUE;

        // Set an array of checked attributes for later comparison against the
        // default matching product.
        if (empty($attribute_names)) {
          $attribute_names = $user_input['attributes'];
          $unchanged_attributes = $user_input['unchanged_attributes'];
        }

        foreach ($attribute_names as $key => $value) {
          // If this is the attribute widget that was changed...
          if ($value != $unchanged_attributes[$key]) {
            // Store the field name.
            $changed_attribute = $key;
          }

          // If a field name has been stored and we've moved past it to
          // compare the next attribute field...
          if (!empty($changed_attribute) && $changed_attribute != $key) {
            // Wipe subsequent values from the form state so the attribute
            // widgets can use the default values from the new default product.
            unset($user_input['attributes'][$key]);
            $form_state->setUserInput($user_input);

            // Don't accept this as a matching product.
            continue;
          }

          if (!$this->isMatchingVariation($variation, $key, $value)) {
            $match = FALSE;
          }
        }

        if ($match) {
          $this->defaultVariation = $variation;
        }
      }
    }
  }

  /**
   * Resolves the qualifying attributes from a set of variations.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface[] $variations
   *   An array of product variations.
   *
   * @return array
   *   An array of qualified attributes.
   */
  protected function resolveQualifyingAttributes($variations) {
    $qualified_attributes = [];
    $used_options = [];
    $field_has_options = [];

    foreach ($this->getAttributesInfo($variations) as $field_name => $data) {

      // Only add options to the present array that appear on products that
      // match the default value of the previously added attribute widgets.
      foreach ($variations as $variation) {
        // Don't apply this check for the current field being evaluated.
        foreach ($used_options as $used_field_name => $unused) {
          if ($used_field_name == $field_name) {
            continue;
          }

          $default_value = $this->defaultVariation->{$used_field_name}->first()->entity->id();
          if (!$this->isMatchingVariation($variation, $used_field_name, $default_value)) {
            continue 2;
          }
        }

        $field_has_options[$field_name] = TRUE;
        $used_options[$field_name][] = $variation->{$field_name}->first()->entity->id();
      }

      // If for some reason no options for this field are used, remove it
      // from the qualifying fields array.
      if (!empty($field_has_options[$field_name]) && !empty($used_options[$field_name])) {
        $qualified_attributes[$field_name] = $data + [
            'used_options' => $used_options
        ];
      }
    }

    return $qualified_attributes;
  }

  /**
   * Ajax attributes form callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function attributesAjax(array $form, FormStateInterface $form_state) {
    return [
      $form['attributes'],
      $form['unchanged_attributes'],
    ];
  }

}
