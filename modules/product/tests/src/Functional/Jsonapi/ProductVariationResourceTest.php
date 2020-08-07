<?php

namespace Drupal\Tests\commerce_product\Functional\Jsonapi;

use Drupal\commerce_price\Comparator\NumberComparator;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\Tests\jsonapi\Functional\ResourceTestBase;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;
use GuzzleHttp\RequestOptions;
use SebastianBergmann\Comparator\Factory as PhpUnitComparatorFactory;

/**
 * JSON:API resource test for variations.
 *
 * @group commerce
 */
class ProductVariationResourceTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;
  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'path',
    'commerce',
    'commerce_store',
    'commerce_price',
    'commerce_price_test',
    'commerce_product',
    'commerce_product_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'commerce_product_variation';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'commerce_product_variation--default';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected static $uniqueFieldNames = ['sku'];

  /**
   * The product for test variations.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * The test entity.
   *
   * @var \Drupal\commerce_product\Entity\ProductVariationInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $factory = PhpUnitComparatorFactory::getInstance();
    $factory->register(new NumberComparator());
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if ($this->entity === NULL) {
      $store = $this->createStore();
      $this->product = Product::create([
        'type' => 'default',
        'title' => $this->randomMachineName(),
        'stores' => [$store],
      ]);
      $this->product->save();
    }
    $variation = ProductVariation::create([
      'type' => 'default',
      'sku' => '456DEF',
      'product_id' => $this->product->id(),
      'price' => new Price('4.00', 'USD'),
    ]);
    $variation->save();
    return $variation;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $base_url = Url::fromUri('base:/jsonapi/commerce_product_variation/default/' . $this->entity->uuid())->setAbsolute();
    $self_url = clone $base_url;
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'commerce_product_variation--default',
        'links' => [
          'self' => ['href' => $self_url->toString()],
        ],
        'attributes' => [
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'created' => (new \DateTime())->setTimestamp($this->entity->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'default_langcode' => TRUE,
          'drupal_internal__variation_id' => (int) $this->entity->id(),
          'langcode' => 'en',
          'list_price' => NULL,
          'price' => [
            'currency_code' => 'USD',
            'formatted' => '$4.00',
            'number' => '4.00',
          ],
          'sku' => '456DEF',
          'status' => TRUE,
          'title' => $this->entity->label(),
        ],
        'relationships' => [
          'commerce_product_variation_type' => [
            'data' => [
              'id' => ProductVariationType::load('default')->uuid(),
              'type' => 'commerce_product_variation_type--commerce_product_variation_type',
            ],
            'links' => [
              'related' => [
                'href' => $base_url->toString() . '/commerce_product_variation_type',
              ],
              'self' => [
                'href' => $base_url->toString() . '/relationships/commerce_product_variation_type',
              ],
            ],
          ],
          'product_id' => [
            'data' => [
              'id' => $this->product->uuid(),
              'type' => 'commerce_product--default',
            ],
            'links' => [
              'related' => [
                'href' => $base_url->toString() . '/product_id',
              ],
              'self' => [
                'href' => $base_url->toString() . '/relationships/product_id',
              ],
            ],
          ],
          'uid' => [
            'data' => [
              'id' => $this->entity->getOwner()->uuid(),
              'type' => 'user--user',
            ],
            'links' => [
              'related' => [
                'href' => $base_url->toString() . '/uid',
              ],
              'self' => [
                'href' => $base_url->toString() . '/relationships/uid',
              ],
            ],
          ],
        ],
      ],
      'links' => [
        'self' => ['href' => $self_url->toString()],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'commerce_product_variation--default',
        'attributes' => [
          // @todo test title generation by omitting title
          // the base test checks that `title` exists by default.
          'title' => $this->product->label(),
          'sku' => 'ABC123',
          'price' => [
            'currency_code' => 'USD',
            'number' => '8.99',
          ],
        ],
        'relationships' => [
          'product_id' => [
            'data' => [
              'type' => 'commerce_product--default',
              'id' => $this->product->uuid(),
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['view commerce_product']);
        break;

      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['view commerce_product', 'manage default commerce_product_variation']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess() {
    $collection_url = Url::fromRoute('jsonapi.commerce_product_variation--default.collection');
    $collection_filter_url = $collection_url->setOption('query', ['filter[sku]' => $this->entity->getSku()]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(0, $doc['data'], var_export($doc, TRUE));

    $this->setUpAuthorization('GET');

    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = Json::decode((string) $response->getBody());
    $this->assertCount(1, $doc['data']);
    $this->assertSame($this->entity->uuid(), $doc['data'][0]['id']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($method === 'GET') {
      return "The following permissions are required: 'view commerce_product' OR 'view default commerce_product'.";
    }
    if ($method === 'POST') {
      return "The following permissions are required: 'administer commerce_product' OR 'manage default commerce_product_variation'.";
    }
    return "The 'manage default commerce_product_variation' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    $cacheability = parent::getExpectedUnauthorizedAccessCacheability();
    $cacheability->addCacheContexts(['url.query_args:v']);
    $cacheability->addCacheableDependency($this->entity);
    return $cacheability;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $cacheability = parent::getExpectedCacheContexts($sparse_fieldset);
    $cacheability[] = 'store';
    $cacheability[] = 'url.query_args:v';
    sort($cacheability);
    return $cacheability;
  }

  /**
   * {@inheritdoc}
   */
  protected static function getAccessDeniedResponse(EntityInterface $entity, AccessResultInterface $access, Url $via_link, $relationship_field_name = NULL, $detail = NULL, $pointer = NULL) {
    // EntityAccessChecker returns the incorrect access reason for `view label`.
    // @todo remove after https://www.drupal.org/project/drupal/issues/3163558
    if ($access instanceof AccessResultReasonInterface && ($via_link->getRouteName() === 'jsonapi.commerce_product_variation_type--commerce_product_variation_type.individual') && !$access->isAllowed()) {
      $access->setReason("The 'administer commerce_product_type' permission is required.");
    }
    return parent::getAccessDeniedResponse($entity, $access, $via_link, $relationship_field_name, $detail, $pointer);
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove after https://www.drupal.org/project/drupal/issues/3163590
   */
  protected function getNestedIncludePaths($depth = 3) {
    $resource_type_repository = $this->container->get('jsonapi.resource_type.repository');
    $get_nested_relationship_field_names = function (EntityInterface $entity, $depth, $path = "") use (&$get_nested_relationship_field_names, $resource_type_repository) {
      $resource_type = $resource_type_repository->get($entity->getEntityTypeId(), $entity->bundle());
      $relationship_field_names = $this->getRelationshipFieldNames($entity, $resource_type);
      if ($depth > 0) {
        $paths = [];
        foreach ($relationship_field_names as $field_name) {
          $next = ($path) ? "$path.$field_name" : $field_name;
          // @note this is where it gets weird.
          // variation -> type (bundle ref)
          // product -> type (bundle ref)
          // store -> type (bundle ref)
          // jsonapi auto aliases `type` to `{entity_type_id}_type`
          $internal_field_name = $resource_type->getInternalName($field_name);
          if (!is_object($entity->{$internal_field_name})) {
            throw new \RuntimeException("{$entity->getEntityTypeId()}: $field_name ($internal_field_name)");
          }
          if ($target_entity = $entity->{$internal_field_name}->entity) {
            $deep = $get_nested_relationship_field_names($target_entity, $depth - 1, $next);
            $paths = array_merge($paths, $deep);
          }
          else {
            $paths[] = $next;
          }
        }
        return $paths;
      }
      return array_map(function ($target_name) use ($path) {
        return "$path.$target_name";
      }, $relationship_field_names);
    };
    return $get_nested_relationship_field_names($this->entity, $depth);
  }

  /**
   * {@inheritdoc}
   *
   * @todo remove after https://www.drupal.org/project/drupal/issues/3163590
   */
  protected function getRelationshipFieldNames(EntityInterface $entity = NULL, ResourceType  $resource_type = NULL) {
    $entity = $entity ?: $this->entity;
    $resource_type = $resource_type ?: $this->resourceType;
    // Only content entity types can have relationships.
    $fields = $entity instanceof ContentEntityInterface
      ? iterator_to_array($entity)
      : [];
    return array_reduce($fields, function ($field_names, $field) use ($resource_type) {
      /* @var \Drupal\Core\Field\FieldItemListInterface $field */
      if (static::isReferenceFieldDefinition($field->getFieldDefinition())) {
        $field_names[] = $resource_type->getPublicName($field->getName());
      }
      return $field_names;
    }, []);
  }


  /**
   * {@inheritdoc}
   */
  protected static function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Also clear the 'commerce_product' entity access handler cache because
    // the 'commerce_product_variation' access handler delegates access to it.
    // @see \Drupal\commerce_product\ProductVariationAccessControlHandler::checkAccess()
    \Drupal::entityTypeManager()->getAccessControlHandler('commerce_product')->resetCache();
    return parent::entityAccess($entity, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityDuplicate(EntityInterface $original, $key) {
    $dupe = parent::getEntityDuplicate($original, $key);
    assert($dupe instanceof ProductVariationInterface);
    $dupe->setSku('XYZ789');
    return $dupe;
  }

}
