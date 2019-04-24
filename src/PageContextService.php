<?php

namespace Drupal\simple_facebook_pixel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PageContextService.
 *
 * @package Drupal\simple_facebook_pixel
 */
class PageContextService implements PageContextServiceInterface {

  /**
   * The Pixel builder.
   *
   * @var \Drupal\simple_facebook_pixel\PixelBuilderService
   */
  protected $pixelBuilder;

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configFactory;

  /**
   * PageContextService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request.
   * @param \Drupal\simple_facebook_pixel\PixelBuilderService $pixel_builder
   *   The Pixel builder.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, PixelBuilderService $pixel_builder) {
    $this->configFactory = $config_factory->get('simple_facebook_pixel.settings');
    $this->request = $request_stack->getCurrentRequest();
    $this->pixelBuilder = $pixel_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $this->buildNodeData();
    $this->buildTaxonomyTermData();
    $this->buildCommerceProductData();
    $this->buildInitiateCheckout();
    $this->buildPurchase();
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

      if (in_array('commerce_product:' . $commerce_product->bundle(), $view_content_entities)) {
        $data = [
          'content_name' => $commerce_product->getTitle(),
          'content_type' => 'product',
          'content_ids' => [$commerce_product->getDefaultVariation()->getSku()],
          'value' => $commerce_product->getDefaultVariation()->getPrice()->getNumber(),
          'currency' => $commerce_product->getDefaultVariation()->getPrice()->getCurrencyCode(),
        ];

        $this->pixelBuilder->addEvent('ViewContent', $data);
      }
    }
  }

  /**
   * Builds Initiate Checkout event data.
   */
  protected function buildInitiateCheckout() {
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
        $skus[] = $item->getPurchasedEntity()->getSku();

        $contents[] = [
          'id' => $item->getPurchasedEntity()->getSku(),
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

  /**
   * Builds Purchase event data.
   */
  protected function buildPurchase() {
    if (!$this->configFactory->get('purchase_enabled')) {
      return;
    }

    $attributes = $this->request->attributes->all();dsm($attributes);

    if (
      isset($attributes['_route']) &&
      $attributes['_route'] == 'commerce_checkout.form' &&
      isset($attributes['step']) &&
      $attributes['step'] == 'complete'
    ) {
      /** @var \Drupal\commerce_order\Entity\Order $commerce_order */
      $commerce_order = $attributes['commerce_order'];

      $skus = [];
      $contents = [];

      /** @var \Drupal\commerce_order\Entity\OrderItem $item */
      foreach ($commerce_order->getItems() as $item) {
        $skus[] = $item->getPurchasedEntity()->getSku();

        $contents[] = [
          'id' => $item->getPurchasedEntity()->getSku(),
          'quantity' => $item->getQuantity(),
        ];
      }

      $data = [
        'num_items' => count($commerce_order->getItems()),
        'value' => $commerce_order->getTotalPrice()->getNumber(),
        'currency' => $commerce_order->getTotalPrice()->getCurrencyCode(),
        'content_ids' => $skus,
        'contents' => $contents,
        'content_type' => 'product',
      ];

      $this->pixelBuilder->addEvent('Purchase', $data);
    }
  }

}
