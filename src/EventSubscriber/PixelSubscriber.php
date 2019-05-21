<?php

namespace Drupal\simple_facebook_pixel\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\simple_facebook_pixel\PixelBuilderService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class PixelSubscriber.
 *
 * @package Drupal\simple_facebook_pixel\EventSubscriber
 */
class PixelSubscriber implements EventSubscriberInterface {

  /**
   * The Pixel builder.
   *
   * @var \Drupal\simple_facebook_pixel\PixelBuilderService
   */
  protected $pixelBuilder;

  /**
   * PixelSubscriber constructor.
   *
   * @param \Drupal\simple_facebook_pixel\PixelBuilderService $pixel_builder
   *   The Pixel builder.
   */
  public function __construct(PixelBuilderService $pixel_builder) {
    $this->pixelBuilder = $pixel_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['onKernelResponse'];
    $events['commerce_cart.entity.add'][] = ['addToCartEvent'];
    return $events;
  }

  /**
   * Invalidates page cache tags if needed.
   */
  public function onKernelResponse(FilterResponseEvent $event) {
    $response = $event->getResponse();

    if (strpos($response->getContent(), 'CompleteRegistration') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:complete_registration']);
    }

    if (strpos($response->getContent(), '"track", "AddToCart"') !== FALSE) {
      Cache::invalidateTags(['simple_facebook_pixel:add_to_cart']);
    }
  }

  /**
   * @param $event
   */
  public function addToCartEvent($event) {
    $product_variation = $event->getEntity();

    $data = [
      'content_name' => $product_variation->getProduct()->getTitle(),
      'content_type' => 'product',
      'content_ids' => [$product_variation->getSku()],
      'value' => $product_variation->getPrice()->getNumber(),
      'currency' => $product_variation->getPrice()->getCurrencyCode(),
    ];

    $this->pixelBuilder->addEvent('AddToCart', $data, TRUE);
  }

}
