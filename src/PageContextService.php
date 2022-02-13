<?php

namespace Drupal\simple_facebook_pixel;

use Drupal\commerce\Context;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the Page Context Service class.
 *
 * @package Drupal\simple_facebook_pixel
 */
class PageContextService implements PageContextServiceInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configFactory;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The Pixel builder.
   *
   * @var \Drupal\simple_facebook_pixel\PixelBuilderService
   */
  protected $pixelBuilder;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current store.
   *
   * @var \Drupal\commerce_store\CurrentStoreInterface
   */
  protected $currentStore;

  /**
   * The chain base price resolver.
   *
   * @var \Drupal\commerce_price\Resolver\ChainPriceResolverInterface
   */
  protected $chainPriceResolver;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * PageContextService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request.
   * @param \Drupal\simple_facebook_pixel\PixelBuilderService $pixel_builder
   *   The Pixel builder.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    PixelBuilderService $pixel_builder,
    EntityTypeManagerInterface $entity_type_manager,
    AccountInterface $current_user) {
    $this->configFactory = $config_factory->get('simple_facebook_pixel.settings');
    $this->request = $request_stack->getCurrentRequest();
    $this->pixelBuilder = $pixel_builder;
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;

    if (
      \Drupal::hasService('commerce_store.current_store') &&
      \Drupal::hasService('commerce_price.chain_price_resolver')
    ) {
      $this->currentStore = \Drupal::service('commerce_store.current_store');
      $this->chainPriceResolver = \Drupal::service('commerce_price.chain_price_resolver');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->buildNodeData();
    $this->buildTaxonomyTermData();
    $this->buildCommerceProductData();
    $this->buildInitiateCheckout();
  }

  /**
   * Builds node view content event data.
   */
  protected function buildNodeData() {
    $node = $this->request->attributes->get('node');

    if ($node instanceof NodeInterface) {
      $view_content_entities = array_values($this->configFactory->get('view_content_entities'));

      if (in_array('node:' . $node->bundle(), $view_content_entities)) {
        $data = [
          'content_name' => $node->getTitle(),
          'content_type' => $node->bundle(),
          'content_ids' => [$node->id()],
        ];

        $this->pixelBuilder->addEvent('ViewContent', $data);
      }
    }
  }

  /**
   * Builds taxonomy term view content event data.
   */
  protected function buildTaxonomyTermData() {
    $taxonomy_term = $this->request->attributes->get('taxonomy_term');

    // In some cases $taxonomy_term can be just term ID -- not a term object.
    if (is_numeric($taxonomy_term)) {
      $taxonomy_term = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->load($taxonomy_term);
    }

    if ($taxonomy_term instanceof TermInterface) {
      $view_content_entities = array_values($this->configFactory->get('view_content_entities'));

      if (in_array('taxonomy_term:' . $taxonomy_term->bundle(), $view_content_entities)) {
        $data = [
          'content_name' => $taxonomy_term->getName(),
          'content_type' => $taxonomy_term->bundle(),
          'content_ids' => [$taxonomy_term->id()],
        ];

        $this->pixelBuilder->addEvent('ViewContent', $data);
      }
    }
  }

  /**
   * Builds commerce product view content event data.
   */
  protected function buildCommerceProductData() {
    if (!class_exists('Drupal\commerce_product\Entity\Product')) {
      return;
    }

    $commerce_product = $this->request->attributes->get('commerce_product');

    if ($commerce_product instanceof \Drupal\commerce_product\Entity\ProductInterface) {
      $view_content_entities = array_values($this->configFactory->get('view_content_entities'));

      if (
        in_array('commerce_product:' . $commerce_product->bundle(), $view_content_entities) &&
        $commerce_product->getDefaultVariation()
      ) {
        $product_variation = $commerce_product->getDefaultVariation();
        $context = new Context($this->currentUser, $this->currentStore->getStore());
        $resolved_price = $this->chainPriceResolver->resolve($product_variation, 1, $context);

        $data = [
          'content_name' => $commerce_product->getTitle(),
          'content_type' => 'product',
          'content_ids' => [$product_variation->getSku()],
          'value' => $resolved_price->getNumber(),
          'currency' => $resolved_price->getCurrencyCode(),
        ];

        $this->pixelBuilder->addEvent('ViewContent', $data);
      }
    }
  }

  /**
   * Builds Initiate Checkout event data.
   */
  protected function buildInitiateCheckout() {
    if (!class_exists('Drupal\commerce_product\Entity\Product')) {
      return;
    }

    if (!$this->configFactory->get('initiate_checkout_enabled')) {
      return;
    }

    $attributes = $this->request->attributes->all();

    if (
      isset($attributes['_route']) &&
      $attributes['_route'] == 'commerce_checkout.form' &&
      isset($attributes['step']) &&
      $attributes['step'] == 'order_information'
    ) {
      /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
      $commerce_order = $attributes['commerce_order'];

      $skus = [];
      $contents = [];

      /** @var \Drupal\commerce_order\Entity\OrderItem $item */
      foreach ($commerce_order->getItems() as $item) {
        $purchased_entity = $item->getPurchasedEntity();
        if (!$purchased_entity) {
          continue;
        }

        $skus[] = $purchased_entity->getSku();
        $contents[] = [
          'id' => $purchased_entity->getSku(),
          'quantity' => $item->getQuantity(),
        ];
      }

      $data = [
        'num_items' => count($commerce_order->getItems()),
        'value' => $commerce_order->getTotalPrice()->getNumber(),
        'currency' => $commerce_order->getTotalPrice()->getCurrencyCode(),
        'content_ids' => $skus,
        'contents' => $contents,
      ];

      $this->pixelBuilder->addEvent('InitiateCheckout', $data);
    }
  }

}
