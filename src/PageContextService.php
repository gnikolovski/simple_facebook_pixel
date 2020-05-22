<?php

namespace Drupal\simple_facebook_pixel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
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
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, PixelBuilderService $pixel_builder, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory->get('simple_facebook_pixel.settings');
    $this->request = $request_stack->getCurrentRequest();
    $this->pixelBuilder = $pixel_builder;
    $this->entityTypeManager = $entity_type_manager;
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
