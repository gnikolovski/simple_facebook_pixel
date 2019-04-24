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
  public function populate() {
    $this->populateNodeData();
    $this->populateTaxonomyTermData();
    $this->populateCommerceProductData();
  }

  /**
   * Populates events data for the current node.
   */
  protected function populateNodeData() {
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
   * Populates events data for the current taxonomy term.
   */
  protected function populateTaxonomyTermData() {
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
   * Populates events data for the current commerce product.
   */
  protected function populateCommerceProductData() {
    if (!class_exists('Drupal\commerce_product\Entity\ProductInterface')) {
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

}
