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

}
